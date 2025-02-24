const express = require('express');
const router = express.Router();
const { criarConexao } = require('../conexao');

router.post('/api/salvar-subscription', async (req, res) => {
    let connection;
    try {
        connection = await criarConexao();
        const userId = req.session.userId; // Assume que você tem autenticação
        const subscription = JSON.stringify(req.body);
        
        await connection.execute(
            'UPDATE usuarios SET push_subscription = ? WHERE id = ?',
            [subscription, userId]
        );
        
        res.json({ success: true });
    } catch (error) {
        console.error('Erro ao salvar subscription:', error);
        res.status(500).json({ error: 'Erro ao salvar subscription' });
    } finally {
        if (connection) await connection.end();
    }
});

module.exports = router; 