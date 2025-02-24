const webpush = require('web-push');
require('dotenv').config();

// Gere suas VAPID keys usando: ./node_modules/.bin/web-push generate-vapid-keys
const vapidKeys = {
    publicKey: process.env.VAPID_PUBLIC_KEY,
    privateKey: process.env.VAPID_PRIVATE_KEY
};

// Configurar as credenciais VAPID
webpush.setVapidDetails(
    'mailto:robertokantovitzlara@gmail.com',
    process.env.VAPID_PUBLIC_KEY,
    process.env.VAPID_PRIVATE_KEY
);

async function enviarNotificacaoPush(subscription, dados) {
    try {
        // Criar o payload da notificação
        const notificationPayload = JSON.stringify({
            title: dados.title,
            options: {
                body: dados.body,
                icon: dados.icon,
                badge: dados.badge,
                vibrate: dados.vibrate,
                data: dados.data,
                dir: 'ltr',
                lang: 'pt-BR',
                renotify: true,
                requireInteraction: true,
                tag: 'medication-reminder'
            }
        });

        await webpush.sendNotification(subscription, notificationPayload);
    } catch (error) {
        console.error('Erro ao enviar notificação push:', error);
        throw error;
    }
}

module.exports = { enviarNotificacaoPush, vapidKeys }; 