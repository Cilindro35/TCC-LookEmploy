var formulario = document.getElementById('formulario');
var tipoInput = document.getElementById('tipo');
var emailInput = document.getElementById('email');
var senhaInput = document.getElementById('senha');

var emailErro = document.getElementById('emailInvalido');
var senhaErro = document.getElementById('senhaInvalida');
var msgSucesso = document.getElementById('msgSucesso');

const padraoEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const padraoLetra = /[a-zA-Z]/;
const padraoNumero = /[0-9]/;

// ---------- VALIDAÇÕES ----------

function validarEmail(email) {
  if (email === "") {
    emailErro.textContent = "Preencha o campo do e-mail*";
    return false;
  }
  if (!padraoEmail.test(email)) {
    emailErro.textContent = "E-mail inválido*";
    return false;
  }
  emailErro.textContent = "";
  return true;
}

function validarSenha(senha) {
  if (senha === "") {
    senhaErro.textContent = "Preencha o campo da senha*";
    return false;
  }
  if (senha.length < 6 || !padraoLetra.test(senha) || !padraoNumero.test(senha)) {
    senhaErro.textContent = "Senha inválida";
    return false;
  }
  senhaErro.textContent = "";
  return true;
}

// ---------- ENVIO DO FORMULÁRIO ----------

async function enviarFormulario(tipo, email, senha) {
  const dados = new FormData();
  dados.append("tipo", tipo);
  dados.append("email", email);
  dados.append("senha", senha);

  try {
    msgSucesso.innerHTML = "<p style='color:lightblue'>Enviando...</p>";

    var resposta = await fetch("./php/realizarLogin.php", {
      method: "POST",
      body: dados,
    });

    var texto = await resposta.text();
    console.log(texto);

    if (texto == "EXITO") {
      window.location.href = "perfil.php";
      return;
    }
    msgSucesso.innerHTML = texto;
  } catch (erro) {
    console.error("Erro ao enviar:", erro);
    msgSucesso.innerHTML = "<p style='color:red'>Erro ao enviar cadastro. Tente novamente.</p>";
  }
}

// ---------- SUBMIT ----------

formulario.addEventListener('submit', function(event) {
  event.preventDefault();

  var tipo = tipoInput.value;
  var email = emailInput.value.trim();
  var senha = senhaInput.value.trim();
  
  var emailValido = validarEmail(email);
  var senhaValida = validarSenha(senha);

  if (emailValido && senhaValida) {
      enviarFormulario(tipo, email, senha);
  } else {
    msgSucesso.innerHTML = "";
  }
});
