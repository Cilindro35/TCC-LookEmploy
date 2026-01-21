var formulario = document.getElementById('formulario');

//não necessitam de validação
var clienteInput = document.getElementById('cliente');
var prestadorInput = document.getElementById('prestador');

//necessitam de validação
var dataInput = document.getElementById('data');
var horarioInput = document.getElementById('horario');
var bairroInput = document.getElementById('bairro');
var logradouroInput = document.getElementById('logradouro');
var numeroInput = document.getElementById('numero');
var complementoInput = document.getElementById('complemento');
var descricaoInput = document.getElementById('desc');
var assuntoInput = document.getElementById('assunto');
var metodo = document.getElementById('metodo');

var dataErro = document.getElementById("dataInvalida");
var horarioErro = document.getElementById("horarioInvalido");
var bairroErro = document.getElementById('bairroInvalido');
var logradouroErro = document.getElementById('logradouroInvalido');
var numeroErro = document.getElementById('numeroInvalido');
var complementoErro = document.getElementById('complementoInvalido');
var descricaoErro = document.getElementById('descInvalida');
var assuntoErro = document.getElementById('assuntoInvalido');
var metodoErro = document.getElementById('pagamentoInvalido');
var msgSucesso = document.getElementById('mensagemFinal');

const padraoLetra = /[a-zA-Z]/;
const padraoNumero = /[0-9]/;

// ---------- VALIDAÇÕES ----------

function validarDataHoraServico(data, horario) {

    if (!data || !horario) {
        horarioErro.textContent = "Preencha o campo da data de realização do serviço*";
        return false;
    }

    // Separar dia, mês, ano da data (assumindo formato yyyy-mm-dd)
    const [ano, mes, dia] = data.split("-").map(Number);
    const [hora, minuto] = horario.split(":").map(Number);

    // Criar objeto Date da data e hora informadas
    const dataHoraInformada = new Date(ano, mes - 1, dia, hora, minuto, 0, 0);
    const agora = new Date();

    // Zerar segundos e milissegundos do agora para evitar erros
    agora.setSeconds(0, 0);

    // Se a data for hoje, a hora mínima é agora + 2 horas
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    if (dataHoraInformada < hoje) {
        dataErro.textContent = "A data deve ser hoje ou futura*";
        return false;
    }

    if (dataHoraInformada.toDateString() === hoje.toDateString()) {
        const limite = new Date();
        limite.setHours(agora.getHours() + 2, agora.getMinutes(), 0, 0);
        if (dataHoraInformada < limite) {
            horarioErro.textContent = "A hora deve ser pelo menos 2 horas à frente do horário atual*";
            return false;
        }
    }

    dataErro.textContent = "";
    horarioErro.textContent = "";
    return dataHoraInformada;
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

function validarMetodo() {
  var metodoInput = document.querySelector('input[name="metodo"]:checked');
  if (!metodoInput) {
    metodoErro.textContent = "Escolha uma opção*";
    return false;
  }
  metodoErro.textContent = "";
  return metodoInput.value;
}

function validarDescricao(descricao) {
  if (descricao.replace(/\s+/g, "") === "") {
    descricaoErro.textContent = "Preencha o campo*";
    return false;
  }
  descricaoErro.textContent = "";
  return true;
}

function validarAssunto(assunto) {
  if (String(assunto).trim() === "") {
    assuntoErro.textContent = "Preencha o assunto*";
    return false;
  }
  if (String(assunto).trim().length < 3) {
    assuntoErro.textContent = "O assunto deve ter pelo menos 3 caracteres*";
    return false;
  }
  assuntoErro.textContent = "";
  return true;
}

// ---------- ENVIO DO FORMULÁRIO ----------

//form para clientes
async function enviarFormulario(cliente, prestador, dataServico, bairro, logradouro, numero, complemento, descricao, metodo) {
  const dados = new FormData();
  dados.append("cliente", cliente);
  dados.append("prestador", prestador);
  dados.append("dataServico", dataServico.toISOString());
  dados.append("bairro", bairro);
  dados.append("logradouro", logradouro);
  dados.append("numero", numero);
  dados.append("complemento", complemento);
  dados.append("assunto", assuntoInput ? assuntoInput.value.trim() : "");
  dados.append("descricao", descricao);
  dados.append("metodo", metodo);
  const codigoServicoEl = document.getElementById('codigoServico');
  if (codigoServicoEl && codigoServicoEl.value) {
    dados.append('codigoServico', codigoServicoEl.value);
  }

  try {
    msgSucesso.innerHTML = "<p style='color:lightblue'>Enviando...</p>";

    var resposta = await fetch("./php/contratar.php", {
      method: "POST",
      body: dados,
    });

    var texto = await resposta.text();
    console.log(texto);

    if (texto == "EXITO") {
      window.location.href = "pedidos.php";
      return;
    }
    msgSucesso.innerHTML = "<p style='color:red;'>" + texto + "</p>";
  } catch (erro) {
    console.error("Erro ao enviar:", erro);
    msgSucesso.innerHTML = "<p style='color:red'>Erro na contratação. Tente novamente.</p>";
  }
}

// ---------- SUBMIT ----------

formulario.addEventListener('submit', function(event) {
  event.preventDefault();

  var cliente = clienteInput.value.trim();
  var prestador = prestadorInput.value.trim();
  var data = dataInput.value.trim();
  var horario = horarioInput.value.trim();
  var bairro = bairroInput.value.trim();
  var logradouro = logradouroInput.value.trim();
  var numero = numeroInput.value.trim();
  var complemento = complementoInput.value.trim();
  var descricao = descricaoInput.value; 

  var dataServico = validarDataHoraServico(data, horario);
  var bairroValido = validarBairro(bairro);
  var logradouroValido = validarLogradouro(logradouro);
  var numeroValido = validarNumero(numero);
  var complementoValido = validarComplemento(complemento);
  var descricaoValida = validarDescricao(descricao);
  var assuntoValido = validarAssunto(assuntoInput ? assuntoInput.value : "");
  var metodoSelecionado = validarMetodo();

  console.log(dataServico);

  if (
    cliente &&
    prestador &&
    dataServico &&
    bairroValido &&
    logradouroValido &&
    numeroValido &&
    complementoValido &&
    descricaoValida &&
    assuntoValido &&
    metodoSelecionado
  ) {
      enviarFormulario(cliente, prestador, dataServico, bairro, logradouro, numero, complemento, descricao, metodoSelecionado);
  } else {
    msgSucesso.innerHTML = "";
  }
});
