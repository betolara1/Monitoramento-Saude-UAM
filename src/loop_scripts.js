const { iniciarNotificador } = require('./medicationNotifier');
const { criarConexao } = require('./conexao');

// Teste de conexão inicial
async function testarConexao() {
    try {
        const connection = await criarConexao();
        console.log('Conexão com banco de dados estabelecida!');
        await connection.end();
        return true;
    } catch (error) {
        console.error('Erro ao conectar com banco:', error);
        return false;
    }
}

// Função para formatar timestamp
function getTimestamp() {
    return new Date().toLocaleString('pt-BR');
}

// Iniciar o sistema
async function iniciar() {
    console.log(`[${getTimestamp()}] Iniciando sistema de notificações...`);
    
    // Loop principal
    while (true) {
        try {
            // Testa a conexão antes de cada execução
            const conexaoOk = await testarConexao();
            if (conexaoOk) {
                console.log(`[${getTimestamp()}] Verificando medicamentos...`);
                await iniciarNotificador();
            } else {
                console.error(`[${getTimestamp()}] Erro de conexão, tentando novamente em 1 minuto`);
            }
        } catch (error) {
            console.error(`[${getTimestamp()}] Erro no loop:`, error);
        }

        // Aguarda 1 minuto antes da próxima execução
        await new Promise(resolve => setTimeout(resolve, 60000));
    }
}

// Tratamento de encerramento gracioso
process.on('SIGINT', () => {
    console.log(`[${getTimestamp()}] Encerrando o sistema...`);
    process.exit(0);
});

// Inicia o sistema
iniciar().catch(error => {
    console.error(`[${getTimestamp()}] Erro fatal:`, error);
    process.exit(1);
}); 