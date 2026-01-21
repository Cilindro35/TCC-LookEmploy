(() => {
  function criarModal(prestadorId, servicoId) {
    const overlay = document.createElement('div');
    overlay.className = 'avaliacao-overlay';
    overlay.innerHTML = `
      <div class="avaliacao-modal">
        <h3>Avaliar serviço</h3>
        <div class="stars" role="radiogroup" aria-label="Avaliação">
          ${[1,2,3,4,5].map(n => `<button class="star" data-value="${n}" aria-label="${n} estrela">&#9733;</button>`).join('')}
        </div>
        <textarea class="avaliacao-comentario" rows="3" placeholder="Comentário (opcional)"></textarea>
        <div class="avaliacao-actions">
          <button class="btn-cancelar">Cancelar</button>
          <button class="btn-enviar">Enviar</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    let selected = 5;
    overlay.querySelectorAll('.star').forEach(btn => {
      btn.addEventListener('mouseenter', () => {
        const val = parseInt(btn.dataset.value);
        pintar(val);
      });
      btn.addEventListener('click', () => {
        selected = parseInt(btn.dataset.value);
        pintar(selected);
      });
    });
    overlay.addEventListener('mouseleave', () => pintar(selected));

    function pintar(val) {
      overlay.querySelectorAll('.star').forEach((b,i) => {
        b.style.color = (i < val) ? '#5CE1E6' : '#ccc';
      });
    }
    pintar(selected);

    overlay.querySelector('.btn-cancelar').addEventListener('click', () => {
      overlay.remove();
    });
    overlay.querySelector('.btn-enviar').addEventListener('click', async () => {
      const comentario = overlay.querySelector('.avaliacao-comentario').value.trim();
      try {
        const fd = new FormData();
        fd.append('codigoServico', String(servicoId));
        fd.append('nota', String(selected));
        if (comentario) fd.append('comentario', comentario);
        const res = await fetch('api_chat/avaliacoes.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json();
        if (data && data.ok) {
          alert('Avaliação registrada. Média do prestador: ' + data.media);
          overlay.remove();
          // Opcional: atualizar estrelas visíveis na página se existir elemento
          const avalEl = document.querySelector('.avaliacao');
          if (avalEl) {
            avalEl.innerHTML = '';
            const full = Math.round(data.media);
            for (let i = 0; i < full; i++) {
              const star = document.createElement('i');
              star.className = 'fa-solid fa-star';
              star.style.color = '#5CE1E6';
              avalEl.appendChild(star);
            }
          }
        } else {
          alert(data.error || 'Erro ao salvar avaliação');
        }
      } catch (e) {
        alert('Erro de rede ao enviar avaliação');
      }
    });
  }

  window.abrirModalAvaliacao = function(prestadorId, servicoId) {
    criarModal(prestadorId, servicoId);
  };

  const style = document.createElement('style');
  style.textContent = `
    .avaliacao-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index: 3000; }
    .avaliacao-modal { background:#fff; padding:16px; border-radius:10px; width:320px; box-shadow:0 8px 24px rgba(0,0,0,0.2); display:flex; flex-direction:column; gap:10px; }
    .stars { display:flex; gap:6px; }
    .star { background:transparent; border:none; font-size:24px; cursor:pointer; color:#ccc; }
    .avaliacao-actions { display:flex; justify-content:flex-end; gap:8px; }
    .avaliacao-actions .btn-enviar { background:#5CE1E6; border:none; color:#0f223b; padding:8px 10px; border-radius:8px; cursor:pointer; }
    .avaliacao-actions .btn-cancelar { background:#eee; border:none; color:#333; padding:8px 10px; border-radius:8px; cursor:pointer; }
    .avaliacao-comentario { width:100%; resize: vertical; }
  `;
  document.head.appendChild(style);
})();

