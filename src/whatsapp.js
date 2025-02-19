const venom = require('venom-bot');
const mysql = require('mysql2/promise');
require('dotenv').config();

// Configuração do MySQL
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'medicina'
};

// Função para logging
function logDebug(mensagem) {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${mensagem}`);
}

// Função para verificar se está dentro da janela de 15 minutos
function dentroJanelaEnvio(horarioMedicamento) {
    const agora = new Date();
    const [horas, minutos] = horarioMedicamento.split(':');
    const horaMed = new Date();
    horaMed.setHours(parseInt(horas), parseInt(minutos), 0);
    
    const limiteInicial = new Date(horaMed.getTime() - 15 * 60000); // 15 minutos antes
    
    return agora >= limiteInicial && agora < horaMed;
}

// Função para formatar número de telefone
function formatarTelefone(telefone) {
    let numero = telefone.replace(/\D/g, '');
    if (!numero.startsWith('55')) {
        numero = '55' + numero;
    }
    return numero;
}

// Função para enviar mensagem
async function enviarMensagem(client, telefone, mensagem) {
    try {
        const numero = formatarTelefone(telefone);
        logDebug(`Tentando enviar mensagem para: ${numero}`);
        
        const resultado = await client.sendText(`${numero}@c.us`, mensagem);
        logDebug('Mensagem enviada com sucesso!');
        return resultado;
    } catch (error) {
        logDebug(`Erro ao enviar mensagem: ${error.message}`);
        throw error;
    }
}

// Função para processar medicamentos
async function processarMedicamentos(client) {
    const connection = await mysql.createConnection(dbConfig);
    
    try {
        logDebug("Iniciando processamento de medicamentos");
        const dataAtual = new Date().toISOString().split('T')[0];
        
        // Busca medicamentos ativos
        const [medicamentos] = await connection.execute(`
            SELECT m.*, u.nome, u.telefone 
            FROM medicamentos m 
            INNER JOIN pacientes p ON m.paciente_id = p.id 
            INNER JOIN usuarios u ON p.usuario_id = u.id 
            WHERE (m.data_fim IS NULL OR m.data_fim >= ?) 
            AND (m.data_inicio <= ?)
        `, [dataAtual, dataAtual]);

        logDebug(`Total de medicamentos encontrados: ${medicamentos.length}`);

        for (const med of medicamentos) {
            logDebug(`\nProcessando medicamento: ${med.nome_medicamento} para paciente: ${med.nome}`);

            // Verifica se já foi enviado hoje
            const [jaEnviado] = await connection.execute(`
                SELECT id FROM logs_medicamentos 
                WHERE medicamento_id = ? 
                AND DATE(horario_envio) = CURRENT_DATE 
                AND status = 'enviado'
            `, [med.id]);

            if (jaEnviado.length > 0) {
                logDebug("Mensagem já enviada hoje para este medicamento");
                continue;
            }

            // Verifica janela de envio
            if (dentroJanelaEnvio(med.horario)) {
                try {
                    const horaMed = new Date();
                    const [horas, minutos] = med.horario.split(':');
                    horaMed.setHours(parseInt(horas), parseInt(minutos), 0);
                    
                    const agora = new Date();
                    const minutosRestantes = Math.floor((horaMed - agora) / 60000);

                    // Prepara a mensagem
                    let mensagem = `Lembrete de medicação\n\n`;
                    mensagem += `${med.nome}, você precisará tomar sua medicação ${med.nome_medicamento} `;
                    mensagem += `em ${minutosRestantes > 1 ? minutosRestantes + ' minutos' : '1 minuto'} `;
                    mensagem += `(às ${med.horario})\n`;
                    
                    if (med.dosagem) {
                        mensagem += `Dosagem: ${med.dosagem}`;
                    }

                    // Envia a mensagem
                    await enviarMensagem(client, med.telefone, mensagem);
                    
                    // Registra o envio no banco
                    await connection.execute(`
                        INSERT INTO logs_medicamentos (medicamento_id, horario_envio, status, mensagem)
                        VALUES (?, NOW(), 'enviado', ?)
                    `, [med.id, mensagem]);

                    logDebug('Mensagem registrada no banco de dados');

                } catch (error) {
                    logDebug(`ERRO ao processar: ${error.message}`);
                    
                    // Registra o erro no banco
                    await connection.execute(`
                        INSERT INTO logs_medicamentos (medicamento_id, horario_envio, status, mensagem)
                        VALUES (?, NOW(), 'erro', ?)
                    `, [med.id, error.message]);
                }
            } else {
                logDebug(`Fora da janela de envio para o horário: ${med.horario}`);
            }
        }

    } catch (error) {
        logDebug(`Erro geral: ${error.message}`);
    } finally {
        await connection.end();
    }
}

// Inicializa o cliente
venom
    .create({
        session: 'medicina-session',
        multidevice: true,
        headless: true,
        useChrome: false,
        debug: false,
        logQR: true
    })
    .then((client) => {
        logDebug('Cliente conectado com sucesso!');
        
        // Executa o processamento a cada minuto
        setInterval(() => {
            processarMedicamentos(client);
        }, 60000); // 60000 ms = 1 minuto
        
        // Executa imediatamente na primeira vez
        processarMedicamentos(client);
    })
    .catch((error) => {
        logDebug('Erro ao criar cliente:', error);
    });

// Tratamento de encerramento
process.on('SIGINT', function() {
    logDebug('Encerrando...');
    process.exit();
});

module.exports = { enviarMensagem };