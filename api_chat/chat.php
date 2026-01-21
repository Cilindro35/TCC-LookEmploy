<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content=""width=device-width, initial-scale=1.0">
    <title>Celke - WebSocket</title>
</head>

<body>
    <h2>Chat</h2>
    <label>Nova mensagem: </label>
    <input type="text" name="mensagem" id="mensagem" placeholder="Digite a mensagem..."><br><br>
    
    <input type="button" onclick="enviar()" value="Enviar"><br><br>
    
    <span id="mensagem-chat"></span>

    <script> 
        
        const mensagemChat = document.getElementById("mensagem-chat");

        const ws = new WebSocket('ws://localhost:8080');
        ws.onopen = () => {
            console.log('Conectado!');
        }
        ws.onmessage = (mensagemRecebida) => {
            let resultado = JSON.parse(mensagemRecebida.data);
        
            mensagemChat.insertAdjacentHTML('beforeend',`${resultado.mensagem} <br> `);
        
        }
        const enviar = () =>{

           let mensagem = document.getElementById("mensagem");
           let dados = {
            mensagem: mensagem.value
           }
           ws.send(JSON.stringify(dados));
           
           mensagemChat.insertAdjacentHTML('beforeend',`${mensagem.value} <br> `);
        

           mensagem.value = '';
        }



    </script> 


</body>
</html>