<head>
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
    <script>
        window.OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "<?php echo ONESIGNAL_APP_ID; ?>",
                safari_web_id: "web.onesignal.auto.50d89199-747f-4818-96ca-50d4208129fc", // opcional, para Safari
                allowLocalhostAsSecureOrigin: true,
                notifyButton: {
                    enable: true,
                },
            });
            
            // Quando o usuário aceitar as notificações
            OneSignal.on('subscriptionChange', function (isSubscribed) {
                if (isSubscribed) {
                    OneSignal.getUserId(function(userId) {
                        registrarDispositivo(userId);
                    });
                }
            });
        });

        function registrarDispositivo(playerId) {
            fetch('registrar_dispositivo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    player_id: playerId
                })
            })
            .then(response => response.json())
            .then(data => console.log('Dispositivo registrado:', data))
            .catch(error => console.error('Erro:', error));
        }
    </script>
</head>