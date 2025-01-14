<?php
include "sidebar.php";
include "conexao.php";

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT p.*, u.nome as nome_usuario FROM profissionais p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$profissional = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Profissional de Saúde</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/pt-BR.js"></script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px;
            border: 1px solid #ced4da;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            padding: 8px;
        }

        .select2-results__option {
            padding: 8px;
        }

        #loading {
            margin-top: 5px;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Editar Profissional de Saúde</h2>
        <p>Editando informações de: <?php echo htmlspecialchars($profissional['nome_usuario']); ?></p>
        <form action="atualizar_profissional.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="usuario_id" value="<?php echo $profissional['usuario_id']; ?>">

            <div class="mb-3">
                <label for="especialidade" class="form-label">Especialidade:</label>
                <select class="form-control" id="especialidade" name="especialidade" required>
                    <option value="">Selecione uma especialidade</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="registro_profissional" class="form-label">CRM:</label>
                <input type="text" 
                       class="form-control" 
                       id="registro_profissional" 
                       name="registro_profissional" 
                       value="<?php echo htmlspecialchars($profissional['registro_profissional']); ?>"
                       placeholder="000000/UF"
                       maxlength="8"
                       pattern="[0-9]{6}/[A-Z]{2}"
                       title="Digite o CRM no formato: 000000/UF"
                       required>
                <small class="form-text text-muted">Formato: 000000/UF (exemplo: 123456/SP)</small>
            </div>

            <div class="mb-3">
                <label for="unidade_saude" class="form-label">Unidade de Saúde:</label>
                <select class="form-control" id="unidade_saude" name="unidade_saude" required>
                    <option value="">Selecione uma unidade de saúde</option>
                </select>
                <div id="loading" style="display: none;">Carregando unidades...</div>
            </div>

            <button type="submit" class="btn btn-primary">Atualizar</button>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        // Máscara para o CRM
        $('#registro_profissional').mask('000000/AA', {
            translation: {
                'A': {
                    pattern: /[A-Z]/,
                    optional: false
                }
            },
            onKeyPress: function(crm, e, field, options) {
                const value = $(field).val();
                if (value.includes('/')) {
                    const [numero, uf] = value.split('/');
                    $(field).val(numero + '/' + uf.toUpperCase());
                }
            }
        });

        // Validação do formulário
        $('form').on('submit', function(e) {
            const crm = $('#registro_profissional').val();
            const crmRegex = /^\d{6}\/[A-Z]{2}$/;

            if (!crmRegex.test(crm)) {
                e.preventDefault();
                alert('Por favor, digite o CRM no formato correto: 000000/UF');
                $('#registro_profissional').focus();
                return false;
            }
        });

        // Lista de especialidades
        const especialidades = [
            "Acupuntura",
            "Alergia e Imunologia",
            "Anestesiologia",
            "Angiologia",
            "Cardiologia",
            "Cirurgia Cardiovascular",
            "Cirurgia da Mão",
            "Cirurgia de Cabeça e Pescoço",
            "Cirurgia do Aparelho Digestivo",
            "Cirurgia Geral",
            "Cirurgia Pediátrica",
            "Cirurgia Plástica",
            "Cirurgia Torácica",
            "Cirurgia Vascular",
            "Clínica Médica",
            "Coloproctologia",
            "Dermatologia",
            "Endocrinologia e Metabologia",
            "Endoscopia",
            "Gastroenterologia",
            "Genética Médica",
            "Geriatria",
            "Ginecologia e Obstetrícia",
            "Hematologia e Hemoterapia",
            "Homeopatia",
            "Infectologia",
            "Mastologia",
            "Medicina de Emergência",
            "Medicina de Família e Comunidade",
            "Medicina do Trabalho",
            "Medicina de Tráfego",
            "Medicina Esportiva",
            "Medicina Física e Reabilitação",
            "Medicina Intensiva",
            "Medicina Legal e Perícia Médica",
            "Medicina Nuclear",
            "Medicina Preventiva e Social",
            "Nefrologia",
            "Neurocirurgia",
            "Neurologia",
            "Nutrologia",
            "Oftalmologia",
            "Oncologia Clínica",
            "Ortopedia e Traumatologia",
            "Otorrinolaringologia",
            "Patologia",
            "Patologia Clínica/Medicina Laboratorial",
            "Pediatria",
            "Pneumologia",
            "Psiquiatria",
            "Radiologia e Diagnóstico por Imagem",
            "Radioterapia",
            "Reumatologia",
            "Urologia"
        ];

        // Lista de unidades
        const unidades = [
            "UBS Alvorada",
            "UBS Cecap",
            "UBS Centro",
            "UBS Independência",
            "UBS Jardim Oriente",
            "UBS Mario Dedini",
            "UBS Parque Orlanda",
            "UBS Paulicéia",
            "UBS Piracicamirim",
            "UBS São Jorge",
            "UBS Vila Cristina",
            "UBS Vila Rezende",
            "UBS Vila Sônia",
            "CRAB Boa Esperança",
            "CRAB Nova América",
            "CRAB Piracicamirim",
            "CRAB Vila Rezende",
            "CRAB Vila Sônia",
            "PSF 1° de Maio",
            "PSF Algodoal",
            "PSF Anhumas",
            "PSF Artemis",
            "PSF Boa Esperança",
            "PSF Chapadão",
            "PSF Costa Rica",
            "PSF Jardim Gilda",
            "PSF Jardim Vitória",
            "PSF Monte Líbano",
            "PSF Novo Horizonte",
            "PSF Santa Fé",
            "PSF Santa Rosa",
            "PSF Santo Antonio",
            "PSF São Francisco",
            "PSF Serra Verde",
            "PSF Tanquinho",
            "PSF Tupi",
            "Santa Casa de Piracicaba",
            "Hospital dos Fornecedores de Cana",
            "Hospital Unimed Piracicaba"
        ];

        // Pegar os valores atuais do PHP
        const especialidadeAtual = '<?php echo addslashes($profissional['especialidade']); ?>';
        const unidadeAtual = '<?php echo addslashes($profissional['unidade_saude']); ?>';

        // Configurar select de especialidade
        const $especialidadeSelect = $('#especialidade');
        especialidades.sort().forEach(esp => {
            const $option = $('<option>', {
                value: esp,
                text: esp,
                selected: esp === especialidadeAtual
            });
            $especialidadeSelect.append($option);
        });

        // Configurar select de unidade
        const $unidadeSelect = $('#unidade_saude');
        unidades.sort().forEach(uni => {
            const $option = $('<option>', {
                value: uni,
                text: uni,
                selected: uni === unidadeAtual
            });
            $unidadeSelect.append($option);
        });

        // Inicializar Select2 para ambos os selects
        $('#especialidade, #unidade_saude').select2({
            placeholder: 'Selecione ou digite para buscar',
            language: 'pt-BR',
            width: '100%'
        });

        // Definir os valores iniciais após inicializar o Select2
        if (especialidadeAtual) {
            $especialidadeSelect.val(especialidadeAtual).trigger('change');
        }
        if (unidadeAtual) {
            $unidadeSelect.val(unidadeAtual).trigger('change');
        }
    });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>