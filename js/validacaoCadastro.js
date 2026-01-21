var formulario = document.getElementById('formulario');
var tipoInput = document.getElementById('tipo');
var emailInput = document.getElementById('email');
var senhaInput = document.getElementById('senha');
var nomeInput = document.getElementById('nome');
var sobrenomeInput = document.getElementById('sobrenome');
var telefoneInput = document.getElementById('telefone');
var dataNascimentoInput = document.getElementById('dataNascimento');
var bairroInput = document.getElementById('bairro');
var logradouroInput = document.getElementById('logradouro');
var numeroInput = document.getElementById('numero');
var complementoInput = document.getElementById('complemento');

var emailErro = document.getElementById('emailInvalido');
var senhaErro = document.getElementById('senhaInvalida');
var nomeErro = document.getElementById('nomeInvalido');
var sobrenomeErro = document.getElementById('sobrenomeInvalido');
var telefoneErro = document.getElementById('telefoneInvalido');
var dataNascimentoErro = document.getElementById('dataNascimentoInvalida');
var bairroErro = document.getElementById('bairroInvalido');
var logradouroErro = document.getElementById('logradouroInvalido');
var numeroErro = document.getElementById('numeroInvalido');
var complementoErro = document.getElementById('complementoInvalido');
var sexoErro = document.getElementById('sexoInvalido');
var servicoErro = document.getElementById('servicoInvalido');
var descricaoErro = document.getElementById('descricaoInvalida');
var msgSucesso = document.getElementById('msgSucesso');

const padraoEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const padraoLetra = /[a-zA-Z]/;
const padraoNumero = /[0-9]/;
const regex = /^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/;

var selectTipo = document.getElementById('tipo');
var campoExtra = document.getElementById('campoExtra');
if (selectTipo && campoExtra) {
  function renderCampoExtra() {
    if (selectTipo.value === 'Prestador') {
      campoExtra.style.display = 'block';
    } else {
      campoExtra.style.display = 'none';
    }
  }
  renderCampoExtra();
  selectTipo.addEventListener('change', renderCampoExtra);
}

//filtro do telefone
telefoneInput.addEventListener("input", function (e) {
  let valor = e.target.value.replace(/\D/g, ""); // remove tudo que não é número

  if (valor.length > 10) {
    // celular com 9 dígitos
    valor = valor.replace(/^(\d{2})(\d{5})(\d{4}).*/, "($1) $2-$3");
  } else if (valor.length > 6) {
    // fixo com 8 dígitos
    valor = valor.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, "($1) $2-$3");
  } else if (valor.length > 2) {
    // apenas DDD + começo do número
    valor = valor.replace(/^(\d{2})(\d{0,5})/, "($1) $2");
  } else {
    // só o DDD
    valor = valor.replace(/^(\d*)/, "($1");
  }

  e.target.value = valor;
});

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
    senhaErro.textContent = "A senha deve conter letras, números e ter no mínimo 6 caracteres*";
    return false;
  }
  senhaErro.textContent = "";
  return true;
}

function validarNome(nome) {
  if (nome.trim() === "") {
    nomeErro.textContent = "Preencha o campo do nome*";
    return false;
  }
  if (nome.length < 3) {
    nomeErro.textContent = "O nome deve ter pelo menos 3 caracteres*";
    return false;
  }
  nomeErro.textContent = "";
  return true;
}

function validarSobrenome(sobrenome) {
  if (sobrenome.trim() === "") {
    sobrenomeErro.textContent = "Preencha o campo do sobrenome*";
    return false;
  }
  if (sobrenome.length < 3) {
    sobrenomeErro.textContent = "O sobrenome deve ter pelo menos 3 caracteres*";
    return false;
  }
  sobrenomeErro.textContent = "";
  return true;
}

function validarTelefone(telefone) {
  if (telefone === "") {
    telefoneErro.textContent = "Preencha o campo do telefone*";
    return false;
  }
  if (!regex.test(telefone)) {
    telefoneErro.textContent = "Telefone inválido*";
    return false;
  }
  telefoneErro.textContent = "";
  return true;
}

function validarDataNascimento(dataNascimento) {
  if (!dataNascimento) {
    dataNascimentoErro.textContent = "Preencha o campo da data de nascimento*";
    return false;
  }

  const nascimento = new Date(dataNascimento);
  const hoje = new Date();

  let idade = hoje.getFullYear() - nascimento.getFullYear();
  let mes = hoje.getMonth() - nascimento.getMonth();

  if (
    idade < 0 ||
    (idade === 0 && mes < 0) ||
    nascimento > hoje
  ) {
    dataNascimentoErro.textContent = "Data de nascimento inválida*";
    return false;
  }

  if (idade < 18 || (idade === 18 && mes < 0)) {
    dataNascimentoErro.textContent = "Você deve ter pelo menos 18 anos*";
    return false;
  }

  dataNascimentoErro.textContent = "";
  return true;
}

function validarBairro(bairro) {
  if (bairro.trim() === "") {
    bairroErro.textContent = "Preencha o campo do bairro*";
    return false;
  }
  if (bairro.length < 3) {
    bairroErro.textContent = "O bairro deve conter pelo menos 3 caracteres*";
    return false;
  }
  bairroErro.textContent = "";
  return true;
}

function validarLogradouro(logradouro) {
  if (logradouro.trim() === "") {
    logradouroErro.textContent = "Preencha o campo do logradouro*";
    return false;
  }
  if (logradouro.length < 3) {
    logradouroErro.textContent = "O logradouro deve conter pelo menos 3 caracteres*";
    return false;
  }
  logradouroErro.textContent = "";
  return true;
}

function validarNumero(numero) {
  if (numero.trim() === "") {
    numeroErro.textContent = "Preencha o campo do número*";
    return false;
  }
  if (!/^\d+$/.test(numero)) {
    numeroErro.textContent = "O número deve conter apenas dígitos*";
    return false;
  }
  numeroErro.textContent = "";
  return true;
}

function validarComplemento(complemento) {
  // Complemento é opcional, mas caso seja preenchido, deve ter no máximo 50 caracteres
  if (complemento.length > 50) {
    complementoErro.textContent = "O complemento deve ter no máximo 50 caracteres*";
    return false;
  }
  complementoErro.textContent = "";
  return true;
}

function validarSexo() {
  var sexoInput = document.querySelector('input[name="sexo"]:checked');
  if (!sexoInput) {
    sexoErro.textContent = "Escolha uma opção*";
    return false;
  }
  sexoErro.textContent = "";
  return sexoInput.value;
}

function validarServico() {
  var servicoInput = document.querySelector('input[name="servico"]:checked');
  if (!servicoInput) {
    servicoErro.textContent = "Escolha uma opção*";
    return false;
  }
  servicoErro.textContent = "";
  return servicoInput.value;
}

function validarDescricaoPrestador() {
  var descricaoInput = document.getElementById('descricaoPrestador');
  if (!descricaoInput) return false;
  var texto = descricaoInput.value.trim();
  if (texto.length === 0) {
    descricaoErro.textContent = "Preencha a descrição*";
    return false;
  }
  if (texto.length < 20) {
    descricaoErro.textContent = "A descrição deve ter pelo menos 20 caracteres*";
    return false;
  }
  descricaoErro.textContent = "";
  return texto;
}

// ---------- ENVIO DO FORMULÁRIO ----------

//form para clientes
async function enviarFormulario(tipo, email, senha, nome, sobrenome, telefone, dataNascimento, bairro, logradouro, numero, complemento, sexo) {
  const dados = new FormData();
  dados.append("tipo", tipo);
  dados.append("email", email);
  dados.append("senha", senha);
  dados.append("nome", nome);
  dados.append("sobrenome", sobrenome);
  dados.append("telefone", telefone.replace(/\D/g, ''));
  dados.append("dataNascimento", dataNascimento);
  dados.append("bairro", bairro);
  dados.append("logradouro", logradouro);
  dados.append("numero", numero);
  dados.append("complemento", complemento);
  dados.append("sexo", sexo);

  try {
    msgSucesso.innerHTML = "<p style='color:lightblue'>Enviando...</p>";

    var resposta = await fetch("./php/realizarCadastro.php", {
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

//form para prestadores
async function enviarFormularioPrestador(tipo, email, senha, nome, sobrenome, telefone, dataNascimento, bairro, logradouro, numero, complemento, sexo, servico, descricao) {
  const dados = new FormData();
  dados.append("tipo", tipo);
  dados.append("email", email);
  dados.append("senha", senha);
  dados.append("nome", nome);
  dados.append("sobrenome", sobrenome);
  dados.append("telefone", telefone.replace(/\D/g, ''));
  dados.append("dataNascimento", dataNascimento);
  dados.append("bairro", bairro);
  dados.append("logradouro", logradouro);
  dados.append("numero", numero);
  dados.append("complemento", complemento);
  dados.append("sexo", sexo);
  dados.append("servico", servico);
  dados.append("descricao", descricao);

  try {
    msgSucesso.innerHTML = "<p style='color:lightblue'>Enviando...</p>";

    var resposta = await fetch("./php/realizarCadastro.php", {
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
  var nome = nomeInput.value.trim();
  var sobrenome = sobrenomeInput.value.trim();
  var telefone = telefoneInput.value.trim();
  var data = dataNascimentoInput.value.trim();
  var bairro = bairroInput.value.trim();
  var logradouro = logradouroInput.value.trim();
  var numero = numeroInput.value.trim();
  var complemento = complementoInput.value.trim();
  
  var sexo = validarSexo();
  var servico;
  var descricao;
  if(tipo == "Prestador") { 
    servico = validarServico();
    descricao = validarDescricaoPrestador();
  }
  var emailValido = validarEmail(email);
  var senhaValida = validarSenha(senha);
  var nomeValido = validarNome(nome);
  var sobrenomeValido = validarSobrenome(sobrenome);
  var telefoneValido = validarTelefone(telefone);
  var dataNascimentoValida = validarDataNascimento(data);
  var bairroValido = validarBairro(bairro);
  var logradouroValido = validarLogradouro(logradouro);
  var numeroValido = validarNumero(numero);
  var complementoValido = validarComplemento(complemento);

  if (
    emailValido &&
    senhaValida &&
    nomeValido &&
    sobrenomeValido &&
    telefoneValido &&
    dataNascimentoValida &&
    bairroValido &&
    logradouroValido &&
    numeroValido &&
    complementoValido &&
    sexo
  ) {
    if(tipo == "Prestador" && servico && descricao) {
      enviarFormularioPrestador(tipo, email, senha, nome, sobrenome, telefone, data, bairro, logradouro, numero, complemento, sexo, servico, descricao);
    } else {
      enviarFormulario(tipo, email, senha, nome, sobrenome, telefone, data, bairro, logradouro, numero, complemento, sexo);
    }
  } else {
    msgSucesso.innerHTML = "";
  }
});
