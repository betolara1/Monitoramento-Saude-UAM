const mysql = require('mysql2/promise');

// Usando as mesmas configurações do seu conexao.php
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'medicina'
};

// Função para criar conexão
async function criarConexao() {
    try {
        const connection = await mysql.createConnection(dbConfig);
        return connection;
    } catch (error) {
        console.error("Conexão falhou:", error);
        throw error;
    }
}

module.exports = { criarConexao }; 