const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const { criarConexao } = require('./conexao');

const app = express();

// Configurar CORS para permitir requisições do seu frontend
app.use(cors({
    origin: 'http://localhost'  // ou 'http://localhost/medicina'
}));

app.use(bodyParser.json());

// Rota para salvar a subscription
app.post('/api/salvar-subscription', async (req, res) => {
    let connection;
    try {
        const { userId, subscription } = req.body;
        
        if (!userId || !subscription) {
            return res.status(400).json({ 
                error: 'userId e subscription são obrigatórios' 
            });
        }

        connection = await criarConexao();
        
        // Salva a subscription no banco
        await connection.execute(
            'UPDATE usuarios SET push_subscription = ? WHERE id = ?',
            [JSON.stringify(subscription), userId]
        );
        
        console.log(`Subscription salva para usuário ${userId}`);
        res.json({ success: true });
    } catch (error) {
        console.error('Erro ao salvar subscription:', error);
        res.status(500).json({ error: error.message });
    } finally {
        if (connection) await connection.end();
    }
});

const PORT = 3000;
app.listen(PORT, () => {
    console.log(`Servidor rodando na porta ${PORT}`);
}); 