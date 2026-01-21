fetch("menuLateral.html")
        .then(res => res.text())
        .then(html => {
            document.getElementById("inserirMenuLateral").innerHTML = html;
            const ensureStyle = href => {
                if (!document.querySelector(`link[href="${href}"]`)) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = href;
                    document.head.appendChild(link);
                }
            };
            ensureStyle('css/design_perfil.css');
            ensureStyle('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
            const navEl = document.querySelector('header nav');
            if (navEl) { navEl.style.justifyContent = 'space-between'; }
            const badge = document.getElementById('notifBadge');
            const btn = document.getElementById('notifBtn');

            async function updateBadge() {
                try {
                    const res = await fetch('api_chat/notifications_count.php', { credentials: 'same-origin' });
                    const data = await res.json();
                    const count = Number(data.count || 0);
                    if (count > 0) {
                        badge.textContent = String(count);
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                } catch (e) {
                    badge.style.display = 'none';
                }
            }

            updateBadge();
            setInterval(updateBadge, 15000);
            btn?.addEventListener('click', () => {
                window.location.href = 'contatos.php';
            });
        });
