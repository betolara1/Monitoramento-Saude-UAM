<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste WebPushr</title>

    <!-- WebPushr Script -->
    <script src="https://cdn.webpushr.com/sw-server.min.js"></script>
    <script>
        (function(w,d, s, id) {
            if(typeof(w.webpushr)!=='undefined') return;
            w.webpushr=w.webpushr||function(){(w.webpushr.q=w.webpushr.q||[]).push(arguments)};
            var js, fjs = d.getElementsByTagName(s)[0];
            js = d.createElement(s); js.id = id;
            js.src = "https://cdn.webpushr.com/app.min.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(window,document, 'script', 'webpushr-jssdk'));

        // Substitua XXXX-XXXX-XXXX-XXXX pelo seu Site ID do WebPushr
        webpushr('init','3fcc0472971bb754a0d05f27f7b8551c');
    </script>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .status {
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <h1>Teste de Notificações Push</h1>
    
    <button class="btn" onclick="solicitarPermissao()">
        Solicitar Permissão para Notificações
    </button>

    <button class="btn" onclick="enviarNotificacao()" style="background-color: #2196F3;">
        Enviar Notificação de Teste
    </button>

    <div id="status" class="status"></div>

    <script>
        function solicitarPermissao() {
            const statusDiv = document.getElementById('status');

            if ('Notification' in window) {
                Notification.requestPermission()
                    .then(function(permission) {
                        if (permission === 'granted') {
                            statusDiv.className = 'status success';
                            statusDiv.textContent = 'Permissão concedida! Você receberá notificações.';
                        } else {
                            statusDiv.className = 'status error';
                            statusDiv.textContent = 'Permissão negada para notificações.';
                        }
                    });
            } else {
                statusDiv.className = 'status error';
                statusDiv.textContent = 'Este navegador não suporta notificações push.';
            }
        }

        // Verifica o status inicial das permissões
        document.addEventListener('DOMContentLoaded', function() {
            const statusDiv = document.getElementById('status');
            
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    statusDiv.className = 'status success';
                    statusDiv.textContent = 'Notificações já estão ativadas!';
                } else if (Notification.permission === 'denied') {
                    statusDiv.className = 'status error';
                    statusDiv.textContent = 'Notificações estão bloqueadas. Por favor, altere nas configurações do navegador.';
                } else {
                    statusDiv.className = 'status';
                    statusDiv.textContent = 'Clique no botão acima para ativar as notificações.';
                }
            }
        });

        function enviarNotificacao() {
            // Usando a API do WebPushr para enviar uma notificação de teste
            fetch('https://api.webpushr.com/v1/notification/send/all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'webpushrKey': '3fcc0472971bb754a0d05f27f7b8551c',
                    'webpushrAuthToken': '105367'
                },
                body: JSON.stringify({
                    title: 'Teste de Notificação',
                    message: 'Esta é uma notificação de teste enviada às ' + new Date().toLocaleTimeString(),
                    target_url: 'https://sua-url.com'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Notificação enviada:', data);
                alert('Notificação enviada com sucesso!');
            })
            .catch(error => {
                console.error('Erro ao enviar notificação:', error);
                alert('Erro ao enviar notificação: ' + error.message);
            });
        }
    </script>
</body>
</html> 