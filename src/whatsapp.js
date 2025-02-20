const venom = require('venom-bot');
const { criarConexao } = require('./conexao');
require('dotenv').config();

// Função para logging
function logDebug(mensagem) {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${mensagem}`);
}

// Função para verificar se está dentro da janela de 15 minutos
function dentroJanelaEnvio(horarioAlvo) {
    const agora = new Date();
    const [horas, minutos] = horarioAlvo.split(':');
    const horarioMedicamento = new Date();
    horarioMedicamento.setHours(parseInt(horas), parseInt(minutos), 0, 0);
    
    // 15 minutos antes do horário
    const inicioJanela = new Date(horarioMedicamento.getTime() - 15 * 60000);
    
    // Verifica se está dentro da janela de 15 minutos
    return agora >= inicioJanela && agora < horarioMedicamento;
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

// Função para calcular todos os horários do dia baseado na frequência
function calcularHorariosDoDia(horarioBase, frequencia) {
    const horarios = [];
    const [horaBase, minutoBase] = horarioBase.split(':').map(Number);
    const freq = parseInt(frequencia);
    
    // Adiciona o horário base
    horarios.push(`${horaBase.toString().padStart(2, '0')}:${minutoBase.toString().padStart(2, '0')}:00`);
    
    // Calcula os próximos horários baseado na frequência
    let proximaHora = horaBase;
    for (let i = 1; i < 24/freq; i++) {
        proximaHora = (proximaHora + freq) % 24;
        horarios.push(`${proximaHora.toString().padStart(2, '0')}:${minutoBase.toString().padStart(2, '0')}:00`);
    }
    
    return horarios;
}

// Função para verificar se já foi enviado
async function verificarEnvioHoje(connection, medicamentoId, horario) {
    const dataAtual = new Date().toISOString().split('T')[0];
    const [registros] = await connection.execute(`
        SELECT COUNT(*) as total
        FROM logs_medicamentos 
        WHERE medicamento_id = ? 
        AND DATE(horario_envio) = ?
        AND ABS(TIME_TO_SEC(TIMEDIFF(TIME(horario_envio), ?))) < 900
        AND status = 'enviado'
    `, [medicamentoId, dataAtual, horario]);

    return registros[0].total > 0;
}

// Função principal modificada
async function processarMedicamentos(client) {
    let connection;
    try {
        connection = await criarConexao();
        const dataAtual = new Date().toISOString().split('T')[0];
        
        const [medicamentos] = await connection.execute(`
            SELECT m.*, u.nome, u.telefone 
            FROM medicamentos m 
            INNER JOIN pacientes p ON m.paciente_id = p.id 
            INNER JOIN usuarios u ON p.usuario_id = u.id 
            WHERE (m.data_fim IS NULL OR m.data_fim >= ?) 
            AND (m.data_inicio <= ?)
        `, [dataAtual, dataAtual]);

        for (const med of medicamentos) {
            const horariosDoDia = calcularHorariosDoDia(med.horario, med.frequencia);
            
            for (const horario of horariosDoDia) {
                // Primeiro verifica se já foi enviado
                const jaEnviado = await verificarEnvioHoje(connection, med.id, horario);
                
                if (!jaEnviado && dentroJanelaEnvio(horario)) {
                    try {
                        let mensagem = `Lembrete de medicação\n\n`;
                        mensagem += `${med.nome}, está na hora de tomar ${med.nome_medicamento}\n`;
                        mensagem += `Horário: ${horario.slice(0, 5)}\n`;
                        
                        if (med.dosagem) {
                            mensagem += `Dosagem: ${med.dosagem}\n`;
                        }

                        // Registra antes de enviar para evitar duplicatas
                        await connection.execute(`
                            INSERT INTO logs_medicamentos 
                            (medicamento_id, horario_envio, status, mensagem)
                            VALUES (?, NOW(), 'enviado', ?)
                        `, [med.id, mensagem]);

                        // Envia a mensagem
                        await enviarMensagem(client, med.telefone, mensagem);
                        logDebug(`Mensagem enviada com sucesso para ${med.nome} - ${horario}`);
                    } catch (error) {
                        logDebug(`Erro ao enviar mensagem: ${error.message}`);
                        
                        // Registra o erro
                        await connection.execute(`
                            INSERT INTO logs_medicamentos 
                            (medicamento_id, horario_envio, status, mensagem)
                            VALUES (?, NOW(), 'erro', ?)
                        `, [med.id, error.message]);
                    }
                } else {
                    logDebug(`Pulando envio para ${med.nome} - ${horario} (${jaEnviado ? 'já enviado' : 'fora da janela'})`);
                }
            }
        }

    } catch (error) {
        logDebug(`Erro geral: ${error.message}`);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

// Inicialização do cliente
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
        
        // Executa a cada minuto
        setInterval(() => {
            processarMedicamentos(client).catch(err => {
                logDebug('Erro no processamento:', err);
            });
        }, 60000);
        
        // Primeira execução
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