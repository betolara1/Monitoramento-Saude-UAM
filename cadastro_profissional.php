<?php
include "sidebar.php";
include "conexao.php";

$user_id = $_GET['id'];

// Fetch user details
$stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Profissionais de Saúde</title>
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
        <h2>Cadastro de Profissionais de Saúde</h2>
        <p>Bem-vindo(a), <?php echo htmlspecialchars($user['nome']); ?>!</p>
        <form action="salvar_profissional.php" method="POST">
            <input type="hidden" name="usuario_id" value="<?php echo $user_id; ?>">

            <div class="mb-3">
                <label for="especialidade" class="form-label">Especialidade:</label>
                <select class="form-control" id="especialidade" name="especialidade" required>
                    <option value="">Selecione uma especialidade</option>
                </select>
                <div id="loading-especialidades" style="display: none;">Carregando especialidades...</div>
            </div>

            <div class="mb-3">
                <label for="registro_profissional" class="form-label">CRM:</label>
                <input type="text" 
                       class="form-control" 
                       id="registro_profissional" 
                       name="registro_profissional" 
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

            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch('especialidades.php');
                const especialidades = await response.json();
                
                const select = document.getElementById('especialidade');
                
                especialidades.forEach(esp => {
                    const option = document.createElement('option');
                    option.value = esp.nome;  // Changed from esp.id to esp.nome
                    option.textContent = esp.nome;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Erro ao carregar especialidades:', error);
            }
        });

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
                    // Converte para maiúsculas automaticamente
                    const value = $(field).val();
                    if (value.includes('/')) {
                        const [numero, uf] = value.split('/');
                        $(field).val(numero + '/' + uf.toUpperCase());
                    }
                }
            });

            // Validação adicional antes do envio do formulário
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

            // URL da API do CNES (usando proxy para evitar CORS)
            const API_URL = 'https://cnes.datasus.gov.br/services/estabelecimentos';
            
            // Parâmetros para Piracicaba-SP
            const params = {
                municipio: '354390', // Código IBGE de Piracicaba
                uf: 'SP'
            };

            // Função para carregar as unidades
            async function carregarUnidades() {
                try {
                    $('#loading').show();
                    
                    // Fazendo a requisição
                    const response = await fetch(`${API_URL}?${new URLSearchParams(params)}`);
                    const data = await response.json();
                    
                    // Ordenando por nome
                    const unidades = data.sort((a, b) => a.nomeFantasia.localeCompare(b.nomeFantasia));
                    
                    // Preenchendo o select
                    const select = $('#unidade_saude');
                    unidades.forEach(unidade => {
                        select.append(`<option value="${unidade.nomeFantasia}">
                            ${unidade.nomeFantasia}
                        </option>`);
                    });
                    
                } catch (error) {
                    console.error('Erro ao carregar unidades:', error);
                    // Em caso de erro, carrega lista estática
                    carregarListaEstatica();
                } finally {
                    $('#loading').hide();
                }
            }

            // Lista estática como fallback
            function carregarListaEstatica() {
                const unidadesEstaticas = [
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

                const select = $('#unidade_saude');
                unidadesEstaticas.sort().forEach(unidade => {
                    select.append(`<option value="${unidade}">${unidade}</option>`);
                });

                // Atualiza o Select2 após carregar as opções
                select.trigger('change');
            }

            // Tenta carregar da API primeiro, se falhar usa lista estática
            carregarUnidades().catch(carregarListaEstatica);

            // Lista de especialidades médicas reconhecidas pelo CFM
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

            function carregarEspecialidades() {
                const select = $('#especialidade');
                
                // Ordena alfabeticamente
                especialidades.sort().forEach(esp => {
                    select.append(`<option value="${esp}">${esp}</option>`);
                });

                // Adiciona evento de busca
                select.select2({
                    placeholder: 'Selecione ou digite para buscar',
                    language: 'pt-BR',
                    width: '100%'
                });
            }

            // Carrega as especialidades quando a página carregar
            carregarEspecialidades();

            // Adiciona Select2 ao select de unidade_saude
            $('#unidade_saude').select2({
                placeholder: 'Selecione ou digite para buscar',
                language: 'pt-BR',
                width: '100%'
            });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>