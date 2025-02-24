self.addEventListener('push', function(event) {
    try {
        const data = event.data.json();
        console.log('Dados recebidos:', data); // Debug

        event.waitUntil(
            self.registration.showNotification(data.title, data.options)
        );
    } catch (error) {
        console.error('Erro ao processar notificação:', error);
        
        // Fallback para caso algo dê errado
        event.waitUntil(
            self.registration.showNotification('Lembrete de Medicação', {
                body: 'Hora de tomar seu medicamento',
            })
        );
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/medicina/dashboard/')
    );
});

console.log('Service Worker carregado com sucesso!'); 