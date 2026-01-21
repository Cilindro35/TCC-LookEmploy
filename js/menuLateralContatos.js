const contatos = document.querySelectorAll('.lis_ch li');
        const n_ch = document.querySelector('.nome_ch h1');
        const chatbox = document.querySelector('.chatbox');
        contatos.forEach(contat => {
          contat.addEventListener('click', () => {
            contatos.forEach(c => {
            c.classList.remove('cont_atual');

            c.classList.add('contat');
            });
            contat.classList.add('cont_atual');
            contat.classList.remove('contat');
            n_ch.classList.add('fade_out')
            
            chatbox.classList.add('fade-out');
            setTimeout (() => {
            n_ch.textContent = contat.querySelector('span').textContent;
            n_ch.classList.remove('fade_out')
            n_ch.classList.add('fade_in')
            
            chatbox.classList.remove('fade-out');

            setTimeout(() => {
              n_ch.classList.remove('fade_in');
            },300);
          },300);
          });

        });