var formulario = document.getElementById('formulario');
var tipoInput = document.getElementById('tipo');
var nomeInput = document.getElementById('nome');
var sobrenomeInput = document.getElementById('sobrenome');
var telefoneInput = document.getElementById('telefone');
var bairroInput = document.getElementById('bairro');
var logradouroInput = document.getElementById('logradouro');
var numeroInput = document.getElementById('numero');
var complementoInput = document.getElementById('complemento');
var descricaoInput = document.getElementById('desc');
//foto de perfil
var imagemPerfilInput = document.getElementById('imagemPerfil');
var fotoPreview = document.getElementById('foto');


var nomeErro = document.getElementById('nomeInvalido');
var sobrenomeErro = document.getElementById('sobrenomeInvalido');
var telefoneErro = document.getElementById('telefoneInvalido');
var bairroErro = document.getElementById('bairroInvalido');
var logradouroErro = document.getElementById('logradouroInvalido');
var numeroErro = document.getElementById('numeroInvalido');
var complementoErro = document.getElementById('complementoInvalido');
var descricaoErro = document.getElementById('descInvalida');
var imagemPerfilErro = document.getElementById('fotoInvalida');
var msgSucesso = document.getElementById('msgSucesso');

const padraoLetra = /[a-zA-Z]/;
const padraoNumero = /[0-9]/;
const regex = /^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/;
const imagensPermitidas = ['image/jpeg', 'image/png'];

// ===================== MÁSCARA DO TELEFONE =====================

telefoneInput.addEventListener("input", function (e) {
  let valor = e.target.value.replace(/\D/g, "");

  if (valor.length > 10) {
    valor = valor.replace(/^(\d{2})(\d{5})(\d{4}).*/, "($1) $2-$3");
  } else if (valor.length > 6) {
    valor = valor.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, "($1) $2-$3");
  } else if (valor.length > 2) {
    valor = valor.replace(/^(\d{2})(\d{0,5})/, "($1) $2");
  } else {
    valor = valor.replace(/^(\d*)/, "($1");
  }

  e.target.value = valor;
});

// ===================== VALIDAÇÕES =====================

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
  if (complemento.length > 50) {
    complementoErro.textContent = "O complemento deve ter no máximo 50 caracteres*";
    return false;
  }
  complementoErro.textContent = "";
  return true;
}

function validarDescricao(descricao) {
  if (descricao.replace(/\s+/g, "") === "") {
    descricaoErro.textContent = "Preencha o campo*";
    return false;
  }
  descricaoErro.textContent = "";
  return true;
}

function validarImagemPerfil(imagemPerfil) {
    if (!imagemPerfil) {
        return true;
    }

    // agora é seguro acessar imagemPerfil.type
    if (!imagensPermitidas.includes(imagemPerfil.type)) {
        imagemPerfilErro.textContent = "Tipo de arquivo inválido. Apenas JPEG, JPG e PNG.";
        return false;
    }

    imagemPerfilErro.textContent = "";
    return true;
}

// ===================== PREVIEW DAS IMAGENS =====================

document.getElementById('btnTrocar').addEventListener('click', () => {
    imagemPerfilInput.click();
});

imagemPerfilInput.addEventListener('change', function () {
    const arquivo = this.files[0];

    if (!arquivo) {
        return;
    }
    // cria a URL temporária da imagem
    const urlImagem = URL.createObjectURL(arquivo);

    // aplica no elemento <img>
    fotoPreview.src = urlImagem;

    // remove erro caso exista
    imagemPerfilErro.textContent = "";
});

// ===================== ENVIO - CLIENTE =====================

async function enviarFormulario(tipo, nome, sobrenome, telefone, bairro, logradouro, numero, complemento, descricao, imagemPerfil) {
  const dados = new FormData();
  dados.append("tipo", tipo);
  dados.append("nome", nome);
  dados.append("sobrenome", sobrenome);
  dados.append("telefone", telefone.replace(/\D/g, ''));
  dados.append("bairro", bairro);
  dados.append("logradouro", logradouro);
  dados.append("numero", numero);
  dados.append("complemento", complemento);
  dados.append("descricao", descricao);
  if (imagemPerfil instanceof File) {
      dados.append("imagemPerfil", imagemPerfil);
  }

  try {
    msgSucesso.innerHTML = "<p style='color:lightblue'>Enviando...</p>";

    var resposta = await fetch("./php/atualizarDados.php", {
      method: "POST",
      body: dados,
    });

    var texto = await resposta.text();
    console.log(texto);

    msgSucesso.innerHTML = "<p style='color:lightgreen'>Dados atualizados com sucesso!</p>";
  } catch (erro) {
    console.error("Erro ao enviar:", erro);
    msgSucesso.innerHTML = "<p style='color:red'>Erro ao atualizar dados. Tente novamente.</p>";
  }
}

// ===================== ENVIO - PRESTADOR =====================

async function enviarFormularioPrestador(tipo, nome, sobrenome, telefone, bairro, logradouro, numero, complemento, servico, descricao, imagemPerfil) {
  const dados = new FormData();
  dados.append("tipo", tipo);
  dados.append("nome", nome);
  dados.append("sobrenome", sobrenome);
  dados.append("telefone", telefone.replace(/\D/g, ''));
  dados.append("bairro", bairro);
  dados.append("logradouro", logradouro);
  dados.append("numero", numero);
  dados.append("complemento", complemento);
  dados.append("servico", servico);
  dados.append("descricao", descricao);
  if (imagemPerfil instanceof File) {
      dados.append("imagemPerfil", imagemPerfil);
  }

  try {
    msgSucesso.innerHTML = "<p style='color:lightblue'>Enviando...</p>";

    var resposta = await fetch("./php/atualizarDados.php", {
      method: "POST",
      body: dados,
    });

    var texto = await resposta.text();
    console.log(texto);

    msgSucesso.innerHTML = "<p style='color:lightgreen'>Dados atualizados com sucesso!</p>";
  } catch (erro) {
    console.error("Erro ao enviar:", erro);
    msgSucesso.innerHTML = "<p style='color:red'>Erro ao atualizar dados. Tente novamente.</p>";
  }
}

// ===================== SUBMIT =====================

formulario.addEventListener('submit', function(event) {
  event.preventDefault();

  var tipo = tipoInput.value;
  var nome = nomeInput.value.trim();
  var sobrenome = sobrenomeInput.value.trim();
  var telefone = telefoneInput.value.trim();
  var bairro = bairroInput.value.trim();
  var logradouro = logradouroInput.value.trim();
  var numero = numeroInput.value.trim();
  var complemento = complementoInput.value.trim();
  var descricao = descricaoInput.value;
  var imagemPerfil = imagemPerfilInput.files[0];

  var servico = (tipo == "Prestador")
    ? document.querySelector('input[name="servico"]:checked')?.value
    : null;

  var nomeValido = validarNome(nome);
  var sobrenomeValido = validarSobrenome(sobrenome);
  var telefoneValido = validarTelefone(telefone);
  var bairroValido = validarBairro(bairro);
  var logradouroValido = validarLogradouro(logradouro);
  var numeroValido = validarNumero(numero);
  var complementoValido = validarComplemento(complemento);
  var descricaoValida = validarDescricao(descricao);
  var imagemPerfilValida = validarImagemPerfil(imagemPerfil);
  console.log(imagemPerfil);

  if (
    nomeValido &&
    sobrenomeValido &&
    telefoneValido &&
    bairroValido &&
    logradouroValido &&
    numeroValido &&
    complementoValido &&
    descricaoValida &&
    imagemPerfilValida
  ) {
    if (tipo == "Prestador") {
      enviarFormularioPrestador(
        tipo, nome, sobrenome, telefone, bairro, logradouro,
        numero, complemento, servico, descricao, imagemPerfil
      );
    } else {
      enviarFormulario(
        tipo, nome, sobrenome, telefone, bairro, logradouro,
        numero, complemento, descricao, imagemPerfil
      );
    }
  } else {
    msgSucesso.innerHTML = "<p style='color:red'>Dados incompletos.</p>";
  }
});
