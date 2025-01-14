function validarSenha() {
    const senha = document.getElementById('senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    const erro = document.getElementById('erro-senha');
    const form = document.getElementById('form-cadastro');

    if (senha.value !== confirmarSenha.value) {
        erro.style.display = 'block';
        senha.classList.add('error-field');
        confirmarSenha.classList.add('error-field');
        return false;
    } else {
        erro.style.display = 'none';
        senha.classList.remove('error-field');
        confirmarSenha.classList.remove('error-field');
        return true;
    }
}

function verificarSenhas() {
    const senha = document.getElementById('senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    
    confirmarSenha.addEventListener('input', validarSenha);
    senha.addEventListener('input', validarSenha);
}

window.onload = verificarSenhas;