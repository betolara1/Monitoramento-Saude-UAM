<div class="container">
    <h2>Cadastro de Consulta</h2>
    <form action="salvar_consulta.php" method="POST">
        <div class="form-group">
            <label for="paciente">Paciente:</label>
            <select name="paciente_id" class="form-control" required>
                <option value="">Selecione o paciente</option>
                <?php
                    $query = "SELECT id, nome FROM pacientes ORDER BY nome";
                    $result = mysqli_query($conexao, $query);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                    }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="profissional">Profissional:</label>
            <select name="profissional_id" class="form-control" required>
                <option value="">Selecione o profissional</option>
                <?php
                    $query = "SELECT id, nome FROM profissionais ORDER BY nome";
                    $result = mysqli_query($conexao, $query);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                    }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="data_consulta">Data da Consulta:</label>
            <input type="date" name="data_consulta" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="pressao_arterial">Pressão Arterial:</label>
            <input type="text" name="pressao_arterial" class="form-control" placeholder="Ex: 120/80">
        </div>

        <div class="form-group">
            <label for="glicemia">Glicemia:</label>
            <input type="text" name="glicemia" class="form-control" placeholder="Ex: 99">
        </div>

        <div class="form-group">
            <label for="peso">Peso (kg):</label>
            <input type="number" name="peso" class="form-control" step="0.01">
        </div>

        <div class="form-group">
            <label for="altura">Altura (m):</label>
            <input type="number" name="altura" class="form-control" step="0.01">
        </div>

        <div class="form-group">
            <label for="observacoes">Observações:</label>
            <textarea name="observacoes" class="form-control" rows="4"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Cadastrar Consulta</button>
    </form>
</div> 