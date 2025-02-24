const { criarConexao } = require('./conexao');
const { enviarNotificacaoPush } = require('./webPushManager');

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
    
    const inicioJanela = new Date(horarioMedicamento.getTime() - 15 * 60000);
    return agora >= inicioJanela && agora < horarioMedicamento;
}

// Função para calcular horários do dia baseado na frequência
function calcularHorariosDoDia(horarioBase, frequencia) {
    const horarios = [];
    const [horaBase, minutoBase] = horarioBase.split(':').map(Number);
    const freq = parseInt(frequencia);
    
    horarios.push(`${horaBase.toString().padStart(2, '0')}:${minutoBase.toString().padStart(2, '0')}:00`);
    
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

// Função principal para processar medicamentos
async function processarMedicamentos() {
    let connection;
    try {
        connection = await criarConexao();
        const dataAtual = new Date().toISOString().split('T')[0];
        const timestamp = new Date().toISOString().replace('T', ' ').slice(0, 19);
        
        console.log(`[${timestamp}] Buscando medicamentos...`);

        const [medicamentos] = await connection.execute(`
            SELECT m.*, u.nome, u.push_subscription 
            FROM medicamentos m 
            INNER JOIN pacientes p ON m.paciente_id = p.id 
            INNER JOIN usuarios u ON p.usuario_id = u.id 
            WHERE (m.data_fim IS NULL OR m.data_fim >= ?) 
            AND (m.data_inicio <= ?)
        `, [dataAtual, dataAtual]);

        console.log(`[${timestamp}] Encontrados ${medicamentos.length} medicamentos`);

        if (medicamentos.length === 0) {
            console.log(`[${timestamp}] Nenhum medicamento encontrado para processar`);
            return;
        }

        for (const med of medicamentos) {
            console.log(`[${timestamp}] Processando medicamento: ${med.nome_medicamento} para ${med.nome}`);
            
            const horariosDoDia = calcularHorariosDoDia(med.horario, med.frequencia);
            console.log(`[${timestamp}] Horários calculados:`, horariosDoDia);
            
            for (const horario of horariosDoDia) {
                const jaEnviado = await verificarEnvioHoje(connection, med.id, horario);
                
                if (jaEnviado) {
                    console.log(`[${timestamp}] Pulando envio para ${med.nome} - ${horario} (já enviado)`);
                    continue;
                }

                if (!dentroJanelaEnvio(horario)) {
                    console.log(`[${timestamp}] Pulando envio para ${med.nome} - ${horario} (fora da janela)`);
                    continue;
                }

                try {
                    let mensagem = `Olá ${med.nome}!\n\n`;
                    mensagem += `Está na hora de tomar seu medicamento:\n`;
                    mensagem += `📌 Medicamento: ${med.nome_medicamento}\n`;
                    mensagem += `⏰ Horário: ${horario.slice(0, 5)}\n`;
                    
                    if (med.dosagem) {
                        mensagem += `💊 Dosagem: ${med.dosagem}\n`;
                    }
                    
                    if (med.observacoes) {
                        mensagem += `📝 Observações: ${med.observacoes}\n`;
                    }

                    mensagem += `\nCuide-se bem! 🌟`;

                    // Log antes de registrar
                    console.log(`[${timestamp}] Tentando enviar para ${med.nome} - ${horario}`);

                    await connection.execute(`
                        INSERT INTO logs_medicamentos 
                        (medicamento_id, horario_envio, status, mensagem)
                        VALUES (?, NOW(), 'enviado', ?)
                    `, [med.id, mensagem]);

                    if (med.push_subscription) {
                        const subscription = JSON.parse(med.push_subscription);
                        await enviarNotificacaoPush(subscription, {
                            title: `Olá ${med.nome}!
Está na hora de tomar seu ${med.nome_medicamento}
⏰ Horário: ${horario.slice(0, 5)}
${med.dosagem ? `💊 Dosagem: ${med.dosagem}\n` : ''}`,

                            body: `Está na hora de tomar seu medicamento:
                                📌 Medicamento: ${med.nome_medicamento}
                                ⏰ Horário: ${horario.slice(0, 5)}
                                ${med.dosagem ? `💊 Dosagem: ${med.dosagem}\n` : ''}
                                ${med.observacoes ? `📝 Observações: ${med.observacoes}\n` : ''}
                                Cuide-se bem! 🌟`,
                            vibrate: [200, 100, 200],
                            data: {
                                dateOfArrival: Date.now(),
                                medicamentoId: med.id
                            }
                        });
                        console.log(`[${timestamp}] Notificação enviada para ${med.nome} - ${horario} (enviado)`);
                    } else {
                        console.log(`[${timestamp}] Usuário ${med.nome} não tem push_subscription configurada`);
                    }
                } catch (error) {
                    console.log(`[${timestamp}] Erro ao enviar para ${med.nome} - ${horario}: ${error.message}`);
                    
                    await connection.execute(`
                        INSERT INTO logs_medicamentos 
                        (medicamento_id, horario_envio, status, mensagem)
                        VALUES (?, NOW(), 'erro', ?)
                    `, [med.id, error.message]);
                }
            }
        }
    } catch (error) {
        const timestamp = new Date().toISOString().replace('T', ' ').slice(0, 19);
        console.error(`[${timestamp}] Erro geral: ${error.message}`);
        console.error(error);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

// Função para iniciar o notificador
function iniciarNotificador() {
    logDebug('Iniciando sistema de notificações...');
    
    // Executa a cada minuto
    setInterval(() => {
        processarMedicamentos().catch(err => {
            logDebug('Erro no processamento:', err);
        });
    }, 60000);
    
    // Primeira execução
    processarMedicamentos();
}

// Tratamento de encerramento
process.on('SIGINT', function() {
    logDebug('Encerrando notificador...');
    process.exit();
});

module.exports = { iniciarNotificador }; 