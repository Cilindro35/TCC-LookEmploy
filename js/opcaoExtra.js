document.addEventListener('DOMContentLoaded', function(){
  const select = document.getElementById('tipo');
  const campoExtra = document.getElementById('campoExtra');

  if (select && campoExtra) {
    campoExtra.style.display = select.value === 'Prestador' ? 'block' : 'none';
    select.addEventListener('change', function () {
      campoExtra.style.display = select.value === 'Prestador' ? 'block' : 'none';
    });
  }

  const excluirBtn = document.getElementById('excluirConta');
  if (excluirBtn) {
    excluirBtn.addEventListener('click', async () => {
      const container = document.getElementById('exibirExclusao');
      if (!container) return;

      const resposta = await fetch('confirmarExclusaoConta.php');
      const html = await resposta.text();
      container.innerHTML = html;

      const cancelar = document.getElementById('cancelarExclusao');
      if (cancelar) {
        cancelar.addEventListener('click', () => {
          container.innerHTML = '';
        });
      }
    });
  }
});
