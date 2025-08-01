var formulario = document.getElementById('formulario').addEventListener('submit', function (event) {
    event.preventDefault();
    
    var tipo = document.getElementById('tipoConta').value;
    console.log(tipo);
    var email = document.getElementById('email').value.trim();
    console.log(email);
    var password = document.getElementById('password').value.trim();
    console.log(password);

    const padraoEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const padraoLetra = /[a-zA-Z]/;
    const padraoNumero = /[0-9]/;
    let validade = true;

    if(email == "") {
        document.getElementById("emailInvalido").innerHTML = "Preencha o campo do e-mail*";
        validade = false;
    } else if (!padraoEmail.test(email)) {
        document.getElementById("emailInvalido").innerHTML = "E-mail inválido*";
        validade = false;
    } else {
        document.getElementById("emailInvalido").innerHTML = "";
    }

    if(password == "") {
        document.getElementById("senhaInvalida").innerHTML = "Preencha o campo da senha*";
        validade = false;
    } else if (password.length < 6 || !(padraoLetra.test(password)) || !(padraoNumero.test(password))) {
        document.getElementById("senhaInvalida").innerHTML = "A senha deve ser composto por letras e números e possuir no mínimo 6 caracteres*";
        validade = false;
    } else {
        document.getElementById("senhaInvalida").innerHTML = "";
    }

    if (validade) {
      const dados = new FormData();
      dados.append("tipo", tipo);
      dados.append("email", email);
      dados.append("password", password);

      fetch("salvarFormCadastro.php", {
        method: "POST",
        body: dados
      })
      .then(res => res.text())
      .then(msg => {
        console.log(msg);
      })
      .catch(err => {
        console.log("Erro ao enviar: " + err);
      });
    }
});