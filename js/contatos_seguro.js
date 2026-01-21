/* ===========================================================
   SISTEMA DE CHAT SEGURO COM JWT
=========================================================== */

if (!CURRENT_USER || !CURRENT_USER.id) {
    alert("Sessão expirada. Faça login novamente.");
    window.location.href = "login.html";
}

let ws = null;
let jwtToken = null;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
let lastDateSeparator = null;
let insertedDateLabels = new Set();

/* ELEMENTOS DO DOM */
const chat = document.getElementById("chatMensagens");
const campoBusca = document.getElementById("buscarContato");
const listaUsuariosEl = document.getElementById("listaUsuarios");
const btnEnviar = document.getElementById("btnEnviar");
const inputMensagem = document.getElementById("mensagem");
const nomeTopo = document.getElementById("nomeContatoAtual");
const digitandoArea = document.getElementById("digitandoArea");
const btnAnexo = document.getElementById("btnAnexo");
const fileAnexo = document.getElementById("fileAnexo");

/* ESTADO */
let contactsMap = {};
let activeContact = null;
let typingTimeoutMap = {};
let wsReady = false;
let isReconnecting = false;
let chatBlocked = false;

/* ===========================================================
   OBTER TOKEN JWT
=========================================================== */

async function getJWTToken() {
    try {
        const response = await fetch('api_chat/gerar_token.php', { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Falha ao obter token (' + response.status + ')');
        }
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        jwtToken = data.token;
        console.log("Token JWT obtido com sucesso");
        return true;
        
    } catch (error) {
        console.error("Erro ao obter token:", error);
        alert("Erro de autenticação. Faça login novamente.");
        window.location.href = "login.html";
        return false;
    }
}

/* ===========================================================
   CONEXÃO WEBSOCKET SEGURA
=========================================================== */

async function connectWebSocket() {
    if (isReconnecting) return;
    
    if (!jwtToken) {
        const tokenObtained = await getJWTToken();
        if (!tokenObtained) return;
    }

    try {
        ws = new WebSocket("ws://localhost:8080");
        
        ws.onopen = () => {
            console.log("WebSocket conectado - Autenticando...");
            reconnectAttempts = 0;
            
            // Enviar token para autenticação
            ws.send(JSON.stringify({
                tipo: "auth",
                token: jwtToken
            }));
        };

        ws.onmessage = handleWebSocketMessage;
        ws.onerror = handleWebSocketError;
        ws.onclose = handleWebSocketClose;
        
    } catch (error) {
        console.error("Erro ao conectar WebSocket:", error);
        attemptReconnect();
    }
}

function handleWebSocketMessage(evt) {
    try {
        const data = JSON.parse(evt.data);
        
        switch (data.tipo) {
            case 'auth_success':
                wsReady = true;
                console.log("Autenticado com sucesso:", data.usuario);
                if (data && data.usuario && typeof data.usuario.id === 'number') {
                    CURRENT_USER.id = data.usuario.id;
                    CURRENT_USER.tipo = data.usuario.tipo || CURRENT_USER.tipo;
                    CURRENT_USER.nome = data.usuario.nome || CURRENT_USER.nome;
                }
                showNotification("Conectado ao chat", "success");
                loadContacts();
                break;
                
            case 'error':
                console.error("Erro do servidor:", data.mensagem);
                showNotification(data.mensagem, "error");
                break;
                
            case 'sistema':
                console.log("Mensagem do sistema:", data.mensagem);
                break;
                
            case 'mensagem':
                handleIncomingMessage(data);
                break;
                
            case 'digitando':
                handleTypingIndicator(data, true);
                break;
                
            case 'parou':
                handleTypingIndicator(data, false);
                break;
                
            case 'status':
                handleOnlineStatus(data);
                break;
                
            case 'lido':
                handleReadReceipt(data);
                break;
        }
        
    } catch (error) {
        console.error("Erro ao processar mensagem:", error);
    }
}

function handleWebSocketError(error) {
    console.error("Erro no WebSocket:", error);
    wsReady = false;
}

function handleWebSocketClose(event) {
    console.log("WebSocket desconectado:", event.code, event.reason);
    wsReady = false;
    
    if (event.code !== 1000) {
        attemptReconnect();
    }
}

function attemptReconnect() {
    if (isReconnecting || reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) return;
    
    isReconnecting = true;
    reconnectAttempts++;
    
    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
    
    console.log(`Tentando reconectar em ${delay/1000}s (tentativa ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})`);
    showNotification(`Reconectando em ${delay/1000}s...`, "warning");
    
    setTimeout(() => {
        isReconnecting = false;
        connectWebSocket();
    }, delay);
}

/* ===========================================================
   HANDLERS DE MENSAGENS
=========================================================== */

function handleIncomingMessage(data) {
    const isFromMe = data.remetente_id === CURRENT_USER.id;
    const contactId = isFromMe ? data.destinatario_id : data.remetente_id;
    
    // Atualizar preview do contato
    if (contactsMap[contactId]) {
        let preview = data.mensagem;
        try {
            const j = JSON.parse(data.mensagem);
            if (j && j.tipo === 'arquivo') {
                preview = j.mime && j.mime.startsWith('image/') ? '[Imagem]' : '[Arquivo] ' + (j.nome || '');
            }
        } catch(e) {}
        contactsMap[contactId].lastMsg = String(preview).substring(0, 50);
        contactsMap[contactId].lastTimeISO = data.data_envio;
        
        if (!isFromMe && activeContact !== contactId) {
            contactsMap[contactId].unreadCount++;
        }
        
        renderContactLine(contactsMap[contactId]);
        reorderContactList();
    }
    
    // Adicionar mensagem ao chat se estiver aberto
    if (activeContact === contactId) {
        adicionarMensagem({
            tipo: isFromMe ? "enviada" : "recebida",
            mensagem: data.mensagem,
            hora: data.data_envio
        });
        
        // Marcar como lido se for mensagem recebida
        if (!isFromMe) {
            sendReadReceipt(data.remetente_id);
        }
    }
}

function handleTypingIndicator(data, isTyping) {
    if (activeContact === data.usuario_id) {
        digitandoArea.textContent = isTyping ? `${data.usuario_nome} está digitando...` : "";
        
        if (isTyping) {
            clearTimeout(typingTimeoutMap[data.usuario_id]);
            typingTimeoutMap[data.usuario_id] = setTimeout(() => {
                digitandoArea.textContent = "";
            }, 3000);
        }
    }
}

function handleOnlineStatus(data) {
    if (contactsMap[data.usuario_id]) {
        contactsMap[data.usuario_id].online = data.status === "online";
        renderContactLine(contactsMap[data.usuario_id]);
    }
}

function handleReadReceipt(data) {
    console.log(`Mensagens lidas por ${data.usuario_nome}`);
}

/* ===========================================================
   ENVIO DE MENSAGENS SEGURO
=========================================================== */

function enviarMensagem(destinatarioId, texto) {
    if (!wsReady || !ws || ws.readyState !== WebSocket.OPEN) {
        showNotification("Aguardando conexão. Tente novamente.", "warning");
        return false;
    }
    
    if (!texto || texto.trim().length === 0) {
        return false;
    }
    
    if (texto.length > 5000) {
        showNotification("Mensagem muito longa (máximo 5000 caracteres)", "error");
        return false;
    }
    
    ws.send(JSON.stringify({
        tipo: "mensagem",
        destinatario_id: destinatarioId,
        mensagem: texto.trim()
    }));
    
    return true;
}

function sendReadReceipt(remetenteId) {
    if (!wsReady || !ws || ws.readyState !== WebSocket.OPEN) return;
    
    ws.send(JSON.stringify({
        tipo: "lido",
        remetente_id: remetenteId
    }));
}

function sendTypingIndicator(destinatarioId, isTyping) {
    if (!wsReady || !ws || ws.readyState !== WebSocket.OPEN) return;
    
    ws.send(JSON.stringify({
        tipo: isTyping ? "digitando" : "parou",
        destinatario_id: destinatarioId
    }));
}

/* ===========================================================
   CARREGAMENTO DE CONTATOS E HISTÓRICO
=========================================================== */

async function loadContacts() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const openId = urlParams.get('open');
        const response = await fetch('api_chat/listar_contatos.php' + (openId ? ('?open=' + encodeURIComponent(openId)) : ''), { credentials: 'same-origin' });
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        listaUsuariosEl.innerHTML = '';
        contactsMap = {};
        
        data.forEach(contact => {
            const li = createContactElement(contact);
            listaUsuariosEl.appendChild(li);
            
            contactsMap[contact.id] = {
                id: contact.id,
                name: contact.nome,
                lastMsg: formatPreview(contact.ultima_mensagem || ""),
                lastTimeISO: contact.ultima_data || null,
                unreadCount: contact.nao_lidas || 0,
                online: contact.online === 1,
                el: li
            };
        });
        
        setupContactClickHandlers();
        
        // Verificar se deve abrir um contato específico
        
        if (openId && contactsMap[openId]) {
            // Abrir contato específico
            const contactElement = contactsMap[openId].el;
            if (contactElement) {
                contactElement.click();
            }
        } else if (data.length > 0) {
            // Abrir primeiro contato
            document.querySelector(".usuario")?.click();
        }
        
    } catch (error) {
        console.error("Erro ao carregar contatos:", error);
        showNotification("Erro ao carregar contatos", "error");
    }
}

async function carregarHistorico(contatoId) {
    if (!contatoId) return;
    
    try {
        const response = await fetch(`carregar_historico_seguro.php?contato_id=${contatoId}&limit=50`, { credentials: 'same-origin' });
        const data = await response.json();
        
        chat.innerHTML = "";
        
        if (data && data.error) {
            const msg = String(data.error);
            if (msg.toLowerCase().includes('bloqueado')) {
                chatBlocked = true;
                btnEnviar.disabled = true;
                inputMensagem.disabled = true;
                chat.innerHTML = '<div style="margin:20px auto; max-width:420px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:8px; padding:12px; text-align:center;">Você foi bloqueado por este usuário. Não é possível enviar ou receber mensagens.</div>';
                return;
            } else {
                chatBlocked = false;
                btnEnviar.disabled = false;
                inputMensagem.disabled = false;
                chat.innerHTML = '<div style="text-align: center; color: #888; padding: 20px;">' + escapeHtml(msg) + '</div>';
                return;
            }
        }
        
        lastDateSeparator = null;
        insertedDateLabels.clear();
        
        chatBlocked = false;
        btnEnviar.disabled = false;
        inputMensagem.disabled = false;
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(msg => {
                adicionarMensagem({
                    tipo: msg.remetente_id === CURRENT_USER.id ? "enviada" : "recebida",
                    mensagem: msg.mensagem,
                    hora: msg.data_envio
                });
            });
        }
        
        chat.scrollTop = chat.scrollHeight;
        
        // Marcar mensagens como lidas
        if (Array.isArray(data) && data.length > 0) {
            sendReadReceipt(contatoId);
        }
        
    } catch (error) {
        console.error("Erro ao carregar histórico:", error);
        chat.innerHTML = '<div style="text-align: center; color: #888; padding: 20px;">Erro ao carregar mensagens</div>';
    }
}

/* ===========================================================
   HELPERS
=========================================================== */

function createContactElement(contact) {
    const li = document.createElement("li");
    li.className = "usuario";
    li.dataset.usuario = contact.id;
    
    const previewText = formatPreview(contact.ultima_mensagem || 'Nenhuma mensagem');
    li.innerHTML = `
        <img src="${contact.foto_perfil || 'img/user.png'}" alt="${contact.nome}" class="foto-perfil">
        <div class="info">
            <span class="nome">${escapeHtml(contact.nome)}</span>
            <span class="ultima-msg">${escapeHtml(previewText)}</span>
        </div>
        <span class="hora-nome">${contact.ultima_data ? formatHora(contact.ultima_data) : ''}</span>
        <div class="${contact.online ? 'dot-online' : 'dot-offline'}"></div>
        <div class="badge-unread" style="display: ${contact.nao_lidas > 0 ? 'block' : 'none'}">
            ${contact.nao_lidas > 99 ? '99+' : contact.nao_lidas}
        </div>
        <button class="btn-excluir-contato" title="Excluir contato" aria-label="Excluir contato">
            <i class="fa-solid fa-trash"></i>
        </button>
        <button class="btn-bloquear-contato" title="Bloquear contato" aria-label="Bloquear contato">
            <i class="fa-solid fa-ban"></i>
        </button>
    `;
    
    return li;
}

function getContactName(contactId) {
    return contactsMap[contactId]?.name || "Desconhecido";
}

function formatHora(dataISO) {
    return new Date(dataISO).toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit"
    });
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function formatPreview(msg) {
    try {
        const j = JSON.parse(String(msg));
        if (j && j.tipo === 'arquivo') {
            if (j.mime && String(j.mime).startsWith('image/')) return '[Imagem]';
            const nome = j.nome ? String(j.nome) : '';
            return nome ? `[Arquivo] ${nome}` : '[Arquivo]';
        }
    } catch (e) {}
    return String(msg);
}

function showNotification(message, type = "info") {
    console.log(`[${type.toUpperCase()}] ${message}`);
}

function adicionarMensagem(info) {
    const horaFmt = formatHora(info.hora);
    inserirSeparadorSeNecessario(info.hora);
    let contentHtml = '';
    try {
        const j = JSON.parse(info.mensagem);
        if (j && j.tipo === 'arquivo') {
            if (j.mime && j.mime.startsWith('image/')) {
                contentHtml = `<div class="msg-image"><img src="${escapeHtml(j.url)}" alt="${escapeHtml(j.nome || 'Imagem')}"/></div>`;
            } else {
                const nomeLink = escapeHtml(j.nome || 'Arquivo');
                contentHtml = `<div class="msg-file"><a href="${escapeHtml(j.url)}" target="_blank" rel="noopener noreferrer">${nomeLink}</a></div>`;
            }
        }
    } catch(e) {}
    if (!contentHtml) {
        contentHtml = `<span>${escapeHtml(info.mensagem)}</span>`;
    }
    const html = `
        <div class="${info.tipo === "enviada" ? "msg-enviada" : "msg-recebida"}">
            ${contentHtml}
            <div class="infos-msg"><span class="hora">${horaFmt}</span></div>
        </div>
    `;

    chat.insertAdjacentHTML("beforeend", html);
    chat.scrollTop = chat.scrollHeight;
}

function inserirSeparadorSeNecessario(dataISO) {
    const label = formatarDataMensagem(dataISO);
    if (insertedDateLabels.has(label)) return;
    const existing = chat.querySelector(`.separador-data[data-data="${label}"]`);
    if (existing) { insertedDateLabels.add(label); return; }
    chat.insertAdjacentHTML(
        "beforeend",
        `<div class="separador-data" data-data="${label}"><span>${label}</span></div>`
    );
    lastDateSeparator = label;
    insertedDateLabels.add(label);
}

function formatarDataMensagem(data) {
    const hoje = new Date();
    const ontem = new Date();
    ontem.setDate(ontem.getDate() - 1);
    const d = new Date(data);
    
    if (d.toDateString() === hoje.toDateString()) return "Hoje";
    if (d.toDateString() === ontem.toDateString()) return "Ontem";
    return d.toLocaleDateString("pt-BR");
}

function renderContactLine(contact) {
    const li = contact.el;
    
    const ultimaMsgEl = li.querySelector(".ultima-msg");
    if (ultimaMsgEl) {
        ultimaMsgEl.textContent = contact.lastMsg || "Última mensagem";
    }
    
    const horaEl = li.querySelector(".hora-nome");
    if (horaEl) {
        horaEl.textContent = contact.lastTimeISO ? formatHora(contact.lastTimeISO) : "";
    }
    
    const badge = li.querySelector(".badge-unread");
    if (badge) {
        if (contact.unreadCount > 0) {
            badge.textContent = contact.unreadCount > 99 ? "99+" : contact.unreadCount;
            badge.style.display = "block";
        } else {
            badge.style.display = "none";
        }
    }
    
    li.querySelector(".dot-offline")?.remove();
    li.querySelector(".dot-online")?.remove();
    
    const dot = document.createElement("div");
    dot.className = contact.online ? "dot-online" : "dot-offline";
    li.appendChild(dot);
}

function reorderContactList() {
    const list = Object.values(contactsMap);
    
    list.sort((a, b) => {
        if (!a.lastTimeISO && !b.lastTimeISO) return a.name.localeCompare(b.name);
        if (!a.lastTimeISO) return 1;
        if (!b.lastTimeISO) return -1;
        return new Date(b.lastTimeISO) - new Date(a.lastTimeISO);
    });
    
    list.forEach(c => listaUsuariosEl.appendChild(c.el));
}

function setupContactClickHandlers() {
    document.querySelectorAll(".usuario").forEach(li => {
        li.addEventListener("click", () => {
            document.querySelectorAll(".usuario").forEach(u => u.classList.remove("ativo"));
            li.classList.add("ativo");
            
            activeContact = parseInt(li.dataset.usuario);
            nomeTopo.textContent = contactsMap[activeContact].name;
            digitandoArea.textContent = "";
            
            if (contactsMap[activeContact]) {
                contactsMap[activeContact].unreadCount = 0;
                renderContactLine(contactsMap[activeContact]);
                
                // Enviar "lido" só se WebSocket estiver pronto
                if (wsReady && ws.readyState === WebSocket.OPEN) {
                    sendReadReceipt(activeContact);
                }
            }
            
            carregarHistorico(activeContact);
            inputMensagem.focus();
        });

        const delBtn = li.querySelector('.btn-excluir-contato');
        delBtn?.addEventListener('click', async (ev) => {
            ev.stopPropagation();
            const contatoId = parseInt(li.dataset.usuario);
            if (!contatoId) return;
            const ok = confirm('Deseja excluir este contato e o histórico de mensagens?');
            if (!ok) return;
            try {
                const fd = new FormData();
                fd.append('contato_id', String(contatoId));
                const res = await fetch('api_chat/excluir_contato.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data && data.ok) {
                    li.remove();
                    if (activeContact === contatoId) {
                        activeContact = null;
                        nomeTopo.textContent = 'Selecione um contato';
                        chat.innerHTML = '';
                    }
                    delete contactsMap[contatoId];
                    showNotification('Contato excluído', 'success');
                } else {
                    showNotification(data.error || 'Não foi possível excluir o contato', 'error');
                }
            } catch (e) {
                showNotification('Erro de rede ao excluir contato', 'error');
            }
        });

        const blockBtn = li.querySelector('.btn-bloquear-contato');
        blockBtn?.addEventListener('click', async (ev) => {
            ev.stopPropagation();
            const contatoId = parseInt(li.dataset.usuario);
            if (!contatoId) return;
            const ok = confirm('Deseja bloquear este contato? Você não receberá mais mensagens dele.');
            if (!ok) return;
            try {
                const fd = new FormData();
                fd.append('contato_id', String(contatoId));
                const res = await fetch('api_chat/bloquear_contato.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data && data.ok) {
                    li.remove();
                    if (activeContact === contatoId) {
                        activeContact = null;
                        nomeTopo.textContent = 'Selecione um contato';
                        chat.innerHTML = '';
                    }
                    delete contactsMap[contatoId];
                    showNotification('Contato bloqueado', 'success');
                } else {
                    showNotification(data.error || 'Não foi possível bloquear o contato', 'error');
                }
            } catch (e) {
                showNotification('Erro de rede ao bloquear contato', 'error');
            }
        });
    });
}

/* ===========================================================
   BUSCA DE CONTATOS
=========================================================== */

campoBusca.addEventListener("input", () => {
    const termo = campoBusca.value.toLowerCase().trim();
    document.querySelectorAll(".usuario").forEach(u => {
        const nome = u.querySelector(".nome").textContent.toLowerCase();
        u.style.display = nome.includes(termo) ? "flex" : "none";
    });
});

/* ===========================================================
   EVENT LISTENERS
=========================================================== */

btnEnviar.addEventListener("click", () => {
    if (!activeContact) {
        showNotification("Selecione um contato", "warning");
        return;
    }
    
    const texto = inputMensagem.value.trim();
    if (!texto) return;
    
    if (enviarMensagem(activeContact, texto)) {
        inputMensagem.value = "";
        inputMensagem.focus();
    }
});

inputMensagem.addEventListener("keydown", e => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        btnEnviar.click();
    } else if (activeContact && e.key !== "Enter") {
        sendTypingIndicator(activeContact, true);
    }
});

/* ===========================================================
   INICIALIZAÇÃO
=========================================================== */

// Conectar WebSocket; contatos serão carregados após auth_success
connectWebSocket();
btnAnexo?.addEventListener('click', () => {
    if (!activeContact) {
        showNotification('Selecione um contato', 'warning');
        return;
    }
    fileAnexo?.click();
});

fileAnexo?.addEventListener('change', async () => {
    if (!fileAnexo.files || fileAnexo.files.length === 0) return;
    const file = fileAnexo.files[0];
    if (!file) return;
    try {
        const fd = new FormData();
        fd.append('arquivo', file);
        const res = await fetch('api_chat/upload_anexo.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json();
        if (data && data.ok && data.meta) {
            const msgJson = JSON.stringify({ tipo: 'arquivo', nome: data.meta.nome, url: data.meta.url, mime: data.meta.mime, tamanho: data.meta.tamanho });
            if (wsReady && ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ tipo: 'mensagem', destinatario_id: activeContact, mensagem: msgJson }));
            } else {
                showNotification('Conexão não disponível, tente novamente', 'error');
            }
        } else {
            showNotification(data.error || 'Falha no upload', 'error');
        }
    } catch(e) {
        showNotification('Erro de rede no upload', 'error');
    } finally {
        fileAnexo.value = '';
    }
});
// Modal de imagem
const imageModal = document.getElementById('imageModal');
const modalImg = document.getElementById('modalImg');
let zoomLevel = 1;
let isDragging = false;
let dragStart = { x: 0, y: 0 };
let imgPos = { x: 0, y: 0 };
document.addEventListener('click', (e) => {
    const img = e.target.closest('.msg-image img');
    if (img) {
        modalImg.src = img.getAttribute('src');
        zoomLevel = 1;
        imgPos = { x: 0, y: 0 };
        modalImg.style.transform = `translate(0px, 0px) scale(1)`;
        imageModal.style.display = 'block';
        return;
    }
    if (e.target.classList.contains('image-modal-close') || e.target.classList.contains('image-modal-backdrop')) {
        imageModal.style.display = 'none';
        modalImg.src = '';
    }
});

document.getElementById('zoomIn')?.addEventListener('click', () => {
    zoomLevel = Math.min(zoomLevel + 0.2, 5);
    modalImg.style.transform = `translate(${imgPos.x}px, ${imgPos.y}px) scale(${zoomLevel})`;
});
document.getElementById('zoomOut')?.addEventListener('click', () => {
    zoomLevel = Math.max(zoomLevel - 0.2, 0.3);
    modalImg.style.transform = `translate(${imgPos.x}px, ${imgPos.y}px) scale(${zoomLevel})`;
});
document.getElementById('zoomReset')?.addEventListener('click', () => {
    zoomLevel = 1;
    imgPos = { x: 0, y: 0 };
    modalImg.style.transform = `translate(0px, 0px) scale(1)`;
});

// Wheel zoom
modalImg.addEventListener('wheel', (ev) => {
    ev.preventDefault();
    const delta = Math.sign(ev.deltaY);
    const step = 0.1;
    zoomLevel = delta > 0 ? Math.max(zoomLevel - step, 0.3) : Math.min(zoomLevel + step, 5);
    modalImg.style.transform = `translate(${imgPos.x}px, ${imgPos.y}px) scale(${zoomLevel})`;
});

// Drag to pan
modalImg.addEventListener('mousedown', (ev) => { isDragging = true; dragStart = { x: ev.clientX, y: ev.clientY }; });
window.addEventListener('mousemove', (ev) => {
    if (!isDragging) return;
    const dx = ev.clientX - dragStart.x;
    const dy = ev.clientY - dragStart.y;
    dragStart = { x: ev.clientX, y: ev.clientY };
    imgPos = { x: imgPos.x + dx, y: imgPos.y + dy };
    modalImg.style.transform = `translate(${imgPos.x}px, ${imgPos.y}px) scale(${zoomLevel})`;
});
window.addEventListener('mouseup', () => { isDragging = false; });
