<?php
session_start();
require 'vendor/autoload.php';
use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'connection.php';

// Verificações iniciais
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin_painel.php');
    exit();
}

$evento_id = $_GET['id'];

// Consultas ao banco de dados
$stmt_evento = $pdo->prepare("SELECT nome FROM eventos WHERE id = ?");
$stmt_evento->execute([$evento_id]);
$evento = $stmt_evento->fetch();

$stmt_inscritos = $pdo->prepare("SELECT * FROM inscritos WHERE evento_id = ?");
$stmt_inscritos->execute([$evento_id]);
$inscritos = $stmt_inscritos->fetchAll();

$stmt_cert_config = $pdo->prepare("SELECT * FROM configuracoes_certificado WHERE evento_id = ?");
$stmt_cert_config->execute([$evento_id]);
$config_certificado = $stmt_cert_config->fetch();

// Função para gerar certificado
function gerarCertificado($inscrito_nome, $config_certificado) {
    if (empty($config_certificado['imagem_fundo'])) {
        throw new Exception('O caminho da imagem de fundo está vazio. Verifique as configurações do certificado.');
    }

    $pdf = new Fpdi('l','mm','A4');
    $pdf->AddPage();
    $pdf->Image($config_certificado['imagem_fundo'], 0, 0, 295, 215);
    $pdf->SetFont('Arial', '', 34);
    $pdf->SetXY(0, 75);
    $pdf->Cell(0, 10, utf8_decode($inscrito_nome), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetXY(0, 150);

    $arquivo_pdf = strtolower(str_replace([' ', '/'], ['_', ''], $inscrito_nome . '_' . $evento_nome)) . '_certificado.pdf';
    $pdf->Output('F', $arquivo_pdf);

    return $arquivo_pdf;
}

// Função para enviar emails
function enviarEmail($to, $subject, $body, $attachment = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aviso@corpbusiness.com.br';
        $mail->Password = 'Corp3014#';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('aviso@corpbusiness.com.br', 'Corpbusiness Congressos & Treinamentos');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;

        if ($attachment) {
            $mail->addAttachment($attachment);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar email: " . $e->getMessage());
        return false;
    }
}

// Processamento de ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Confirmar Presença
    if (isset($_POST['confirmar_presenca'])) {
        $inscrito_id = $_POST['inscrito_id'];
        $stmt = $pdo->prepare("UPDATE inscritos SET confirmado_presenca = 1 WHERE id = ?");
        $stmt->execute([$inscrito_id]);
    }

    // Excluir Inscrito
    if (isset($_POST['excluir_inscrito'])) {
        $inscrito_id = $_POST['inscrito_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM inscritos WHERE id = ?");
            $stmt->execute([$inscrito_id]);
            header("Location: inscritos_evento.php?id=$evento_id&acao=excluir_success");
            exit();
        } catch (PDOException $e) {
            error_log("Erro ao excluir inscrito: " . $e->getMessage());
        }
    }

    // Processamento de certificados e materiais
    include 'processar_acoes.php'; // Mova o processamento complexo para um arquivo separado

    if (isset($_POST['confirmar_presenca']) && isset($_POST['toggle_status'])) {
        $inscrito_id = $_POST['inscrito_id'];
        $novo_status = isset($_POST['novo_status']) ? (int)$_POST['novo_status'] : 1;
        
        $stmt = $pdo->prepare("UPDATE inscritos SET confirmado_presenca = ? WHERE id = ?");
        $stmt->execute([$novo_status, $inscrito_id]);
        
        if (isset($_POST['toggle_status'])) {
            http_response_code(200);
            exit;
        }
    }
}

// Contadores
$stmt_total_inscritos = $pdo->prepare("SELECT COUNT(*) FROM inscritos WHERE evento_id = ?");
$stmt_total_inscritos->execute([$evento_id]);
$total_inscritos = $stmt_total_inscritos->fetchColumn();

$stmt_confirmados = $pdo->prepare("SELECT COUNT(*) FROM inscritos WHERE evento_id = ? AND confirmado_presenca = 1");
$stmt_confirmados->execute([$evento_id]);
$total_confirmados = $stmt_confirmados->fetchColumn();

$stmt_a_confirmar = $pdo->prepare("SELECT COUNT(*) FROM inscritos WHERE evento_id = ? AND confirmado_presenca = 0");
$stmt_a_confirmar->execute([$evento_id]);
$total_a_confirmar = $stmt_a_confirmar->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscritos no Evento: <?= htmlspecialchars($evento['nome']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <style>
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-hover:hover {
            animation: pulse 1s infinite;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .status-badge {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.2) 50%,
                rgba(255,255,255,0) 100%
            );
            transition: all 0.5s ease;
        }

        .status-badge:hover::before {
            left: 100%;
        }

        .status-toggle {
            position: relative;
        }

        .status-toggle::after {
            content: 'Clique para alterar status';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            white-space: nowrap;
            z-index: 10;
        }

        .status-toggle:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @keyframes statusChange {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .status-animation {
            animation: statusChange 0.5s ease;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 bg-gray-800 shadow-xl w-[85px] z-50">
        <div class="flex flex-col h-full">
            <!-- Logo Area -->
            <div class="flex items-center justify-center h-16 border-b border-gray-700">
                <i class='bx bx-user-circle text-3xl text-blue-400'></i>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 pt-4">
                <div class="sidebar-item group">
                    <a href="admin_painel.php" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
                        <i class='bx bx-grid-alt text-2xl mb-1'></i>
                        <span class="text-xs">Dashboard</span>
                    </a>
                </div>

                <div class="sidebar-item group active">
                    <a href="#" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
                        <i class='bx bx-calendar-event text-2xl mb-1'></i>
                        <span class="text-xs">Eventos</span>
                    </a>
                </div>

                <div class="sidebar-item group">
                    <a href="#" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
                        <i class='bx bx-certification text-2xl mb-1'></i>
                        <span class="text-xs">Certificados</span>
                    </a>
                </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-[85px]">
        <!-- Top Navigation -->
        <nav class="bg-gray-800 shadow-lg sticky top-0 z-40">
            <div class="mx-auto px-4">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-white">Inscritos: <?= htmlspecialchars($evento['nome']); ?></h1>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <div class="relative group">
                            <button class="flex items-center space-x-3">
                                <img class="h-8 w-8 rounded-full border-2 border-blue-400" src="https://via.placeholder.com/150" alt="Admin avatar">
                                <span class="text-sm font-medium text-gray-300">Administrador</span>
                                <i class='bx bx-chevron-down text-gray-400'></i>
                            </button>
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-xl py-2 hidden group-hover:block">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                    <i class='bx bx-user mr-2'></i>Perfil
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700">
                                    <i class='bx bx-log-out mr-2'></i>Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Após a navbar, antes do main content -->
        <div class="ml-[85px] p-6">
            <a href="admin_painel.php" 
                class="inline-flex items-center px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 mb-6">
                <i class='bx bx-arrow-back mr-2'></i>
                Voltar ao Painel
            </a>

            <!-- Main Content Area -->
            <div class="p-6">
                <!-- Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Inscritos -->
                    <div class="bg-gray-800 p-6 rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Total Inscritos</p>
                                <h3 class="text-2xl font-bold text-blue-400"><?= $total_inscritos ?></h3>
                            </div>
                            <div class="bg-blue-500 bg-opacity-20 p-4 rounded-full">
                                <i class='bx bx-user-plus text-2xl text-blue-400'></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Confirmados -->
                    <div class="bg-gray-800 p-6 rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Confirmados</p>
                                <h3 class="text-2xl font-bold text-green-400"><?= $total_confirmados ?></h3>
                            </div>
                            <div class="bg-green-500 bg-opacity-20 p-4 rounded-full">
                                <i class='bx bx-check-circle text-2xl text-green-400'></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total a Confirmar -->
                    <div class="bg-gray-800 p-6 rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Pendentes</p>
                                <h3 class="text-2xl font-bold text-yellow-400"><?= $total_a_confirmar ?></h3>
                            </div>
                            <div class="bg-yellow-500 bg-opacity-20 p-4 rounded-full">
                                <i class='bx bx-time text-2xl text-yellow-400'></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold mb-4">Ações Rápidas</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button onclick="showUploadCertificado()" 
                            class="flex items-center justify-center p-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors animate-hover">
                            <i class='bx bx-certification mr-2 text-xl'></i>
                            <span>Configurar Certificado</span>
                        </button>

                        <label class="flex items-center justify-center p-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors animate-hover cursor-pointer">
                            <i class='bx bx-upload mr-2 text-xl'></i>
                            <span>Upload Material</span>
                            <input type="file" name="material_upload" class="hidden" onchange="submitMaterial(this)">
                        </label>

                        <button onclick="showTestCertificate()" 
                            class="flex items-center justify-center p-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors animate-hover">
                            <i class='bx bx-test-tube mr-2 text-xl'></i>
                            <span>Certificado de Teste</span>
                        </button>
                    </div>
                </div>

                <!-- Lista de Inscritos -->
                <!-- Total Confirmados -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Confirmados</p>
                            <h3 class="text-2xl font-bold text-green-400"><?= $total_confirmados ?></h3>
                        </div>
                        <div class="bg-green-500 bg-opacity-20 p-4 rounded-full">
                            <i class='bx bx-check-circle text-2xl text-green-400'></i>
                        </div>
                    </div>
                </div>

                <!-- Total a Confirmar -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Pendentes</p>
                            <h3 class="text-2xl font-bold text-yellow-400"><?= $total_a_confirmar ?></h3>
                        </div>
                        <div class="bg-yellow-500 bg-opacity-20 p-4 rounded-full">
                            <i class='bx bx-time text-2xl text-yellow-400'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4">Ações Rápidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="showUploadCertificado()" 
                        class="flex items-center justify-center p-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors animate-hover">
                        <i class='bx bx-certification mr-2 text-xl'></i>
                        <span>Configurar Certificado</span>
                    </button>

                    <label class="flex items-center justify-center p-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors animate-hover cursor-pointer">
                        <i class='bx bx-upload mr-2 text-xl'></i>
                        <span>Upload Material</span>
                        <input type="file" name="material_upload" class="hidden" onchange="submitMaterial(this)">
                    </label>

                    <button onclick="showTestCertificate()" 
                        class="flex items-center justify-center p-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors animate-hover">
                        <i class='bx bx-test-tube mr-2 text-xl'></i>
                        <span>Certificado de Teste</span>
                    </button>
                </div>
            </div>

            <!-- Lista de Inscritos -->
            <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden mb-8">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Lista de Inscritos</h2>
                        <form action="inscritos_evento.php?id=<?= $evento_id; ?>" method="POST">
                            <button type="submit" name="enviar_certificados" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                                <i class='bx bx-envelope mr-2'></i>
                                Enviar Certificados para Todos
                            </button>
                        </form>
                    </div>
                    
                    <!-- Tabela de Inscritos -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-700">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($inscritos as $inscrito): ?>
                                <tr class="hover:bg-gray-700 transition-all duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <i class='bx bx-user-circle text-3xl text-gray-400'></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium"><?= htmlspecialchars($inscrito['nome']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?= htmlspecialchars($inscrito['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="status-toggle group relative" data-inscrito-id="<?= $inscrito['id'] ?>">
                                            <div class="flex items-center cursor-pointer">
                                                <?php if ($inscrito['confirmado_presenca']): ?>
                                                    <span class="status-badge confirmed px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 transition-all duration-300">
                                                        <i class='bx bx-check mr-1'></i>
                                                        Confirmado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge pending px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 transition-all duration-300">
                                                        <i class='bx bx-time mr-1'></i>
                                                        Pendente
                                                    </span>
                                                <?php endif; ?>
                                                <i class='bx bx-chevron-down ml-1 text-gray-400 group-hover:text-white transition-colors duration-200'></i>
                                            </div>
                                            
                                            <!-- Dropdown de Status -->
                                            <div class="status-dropdown hidden group-hover:block absolute top-full left-0 mt-1 w-40 bg-gray-800 rounded-lg shadow-xl z-50">
                                                <div class="py-1">
                                                    <button onclick="changeStatus(this, <?= $inscrito['id'] ?>, 1)" 
                                                        class="status-option w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 flex items-center">
                                                        <i class='bx bx-check mr-2 text-green-400'></i>
                                                        Confirmado
                                                    </button>
                                                    <button onclick="changeStatus(this, <?= $inscrito['id'] ?>, 0)" 
                                                        class="status-option w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 flex items-center">
                                                        <i class='bx bx-time mr-2 text-yellow-400'></i>
                                                        Pendente
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex space-x-2">
                                            <form action="inscritos_evento.php?id=<?= $evento_id; ?>" method="POST" class="inline">
                                                <input type="hidden" name="inscrito_id" value="<?= $inscrito['id'] ?>">
                                                <button type="submit" name="enviar_certificado" 
                                                    class="text-blue-400 hover:text-blue-300" title="Enviar Certificado">
                                                    <i class='bx bx-certification text-xl'></i>
                                                </button>
                                            </form>

                                            <form action="inscritos_evento.php?id=<?= $evento_id; ?>" method="POST" class="inline">
                                                <input type="hidden" name="inscrito_id" value="<?= $inscrito['id'] ?>">
                                                <button type="submit" name="confirmar_presenca" 
                                                    class="text-green-400 hover:text-green-300" title="Confirmar Presença">
                                                    <i class='bx bx-check-circle text-xl'></i>
                                                </button>
                                            </form>

                                            <form action="inscritos_evento.php?id=<?= $evento_id; ?>" method="POST" class="inline">
                                                <input type="hidden" name="inscrito_id" value="<?= $inscrito['id'] ?>">
                                                <button type="submit" name="excluir_inscrito" 
                                                    class="text-red-400 hover:text-red-300" title="Excluir"
                                                    onclick="return confirm('Tem certeza que deseja excluir este inscrito?')">
                                                    <i class='bx bx-trash text-xl'></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal de Upload de Certificado -->
            <div id="uploadCertificadoModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
                        <h3 class="text-lg font-bold mb-4">Configurar Certificado</h3>
                        <form action="inscritos_evento.php?id=<?= $evento_id; ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Imagem de Fundo:</label>
                                <input type="file" name="imagem_fundo" accept="image/*" required
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="hideUploadCertificado()"
                                    class="px-4 py-2 text-gray-300 hover:text-white">
                                    Cancelar
                                </button>
                                <button type="submit" name="salvar_configuracao"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Salvar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal de Certificado de Teste -->
            <div id="testCertificateModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
                        <h3 class="text-lg font-bold mb-4">Enviar Certificado de Teste</h3>
                        <form action="inscritos_evento.php?id=<?= $evento_id; ?>" method="POST">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Email para Teste:</label>
                                <input type="email" name="email_teste" required
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="hideTestCertificate()"
                                    class="px-4 py-2 text-gray-300 hover:text-white">
                                    Cancelar
                                </button>
                                <button type="submit" name="enviar_teste"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Enviar Teste
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showUploadCertificado() {
        document.getElementById('uploadCertificadoModal').classList.remove('hidden');
    }

    function hideUploadCertificado() {
        document.getElementById('uploadCertificadoModal').classList.add('hidden');
    }

    function submitMaterial(input) {
        if (input.files.length > 0) {
            document.getElementById('material-form').submit();
        }
    }

    function showTestCertificate() {
        document.getElementById('testCertificateModal').classList.remove('hidden');
    }

    function hideTestCertificate() {
        document.getElementById('testCertificateModal').classList.add('hidden');
    }

    // Fechar modais ao clicar fora
    document.querySelectorAll('.fixed').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });

    // Configuração do Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };

    // Função para alternar status
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('click', async function() {
            const inscritoId = this.dataset.inscritoId;
            const statusBadge = this.querySelector('.status-badge');
            const isConfirmed = statusBadge.classList.contains('bg-green-100');
            
            // Adiciona animação
            statusBadge.classList.add('status-animation');
            
            try {
                const formData = new FormData();
                formData.append('inscrito_id', inscritoId);
                formData.append('confirmar_presenca', '1');
                formData.append('toggle_status', '1');

                const response = await fetch('inscritos_evento.php?id=<?= $evento_id ?>', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    // Alterna as classes e ícones
                    if (isConfirmed) {
                        statusBadge.classList.remove('bg-green-100', 'text-green-800');
                        statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                        statusBadge.innerHTML = '<i class="bx bx-time mr-1"></i>Pendente';
                        toastr.warning('Status alterado para Pendente');
                    } else {
                        statusBadge.classList.remove('bg-yellow-100', 'text-yellow-800');
                        statusBadge.classList.add('bg-green-100', 'text-green-800');
                        statusBadge.innerHTML = '<i class="bx bx-check mr-1"></i>Confirmado';
                        toastr.success('Status alterado para Confirmado');
                    }
                    
                    // Atualiza os contadores
                    updateCounters();
                }
            } catch (error) {
                console.error('Erro ao alterar status:', error);
                toastr.error('Erro ao alterar status');
            }

            // Remove a classe de animação após a transição
            setTimeout(() => {
                statusBadge.classList.remove('status-animation');
            }, 500);
        });
    });

    // Função para atualizar os contadores
    async function updateCounters() {
        try {
            const response = await fetch(`get_counters.php?evento_id=<?= $evento_id ?>`);
            const counters = await response.json();
            
            // Atualiza os números com animação
            const confirmadosEl = document.querySelector('[data-counter="confirmados"]');
            const pendentesEl = document.querySelector('[data-counter="pendentes"]');
            
            if (confirmadosEl) {
                confirmadosEl.classList.add('status-animation');
                confirmadosEl.textContent = counters.confirmados;
            }
            
            if (pendentesEl) {
                pendentesEl.classList.add('status-animation');
                pendentesEl.textContent = counters.pendentes;
            }

            // Remove as animações
            setTimeout(() => {
                confirmadosEl?.classList.remove('status-animation');
                pendentesEl?.classList.remove('status-animation');
            }, 500);
        } catch (error) {
            console.error('Erro ao atualizar contadores:', error);
        }
    }

    // Adicione este JavaScript para gerenciar a troca de status
    async function changeStatus(button, inscritoId, status) {
        const statusToggle = button.closest('.status-toggle');
        const statusBadge = statusToggle.querySelector('.status-badge');
        
        try {
            const formData = new FormData();
            formData.append('inscrito_id', inscritoId);
            formData.append('confirmar_presenca', '1');
            formData.append('toggle_status', '1');
            formData.append('novo_status', status);

            const response = await fetch('inscritos_evento.php?id=<?= $evento_id ?>', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Adiciona animação
                statusBadge.classList.add('status-animation');
                
                // Atualiza o badge
                if (status === 1) {
                    statusBadge.classList.remove('bg-yellow-100', 'text-yellow-800');
                    statusBadge.classList.add('bg-green-100', 'text-green-800');
                    statusBadge.innerHTML = '<i class="bx bx-check mr-1"></i>Confirmado';
                    toastr.success('Status alterado para Confirmado');
                } else {
                    statusBadge.classList.remove('bg-green-100', 'text-green-800');
                    statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                    statusBadge.innerHTML = '<i class="bx bx-time mr-1"></i>Pendente';
                    toastr.warning('Status alterado para Pendente');
                }
                
                // Atualiza os contadores
                updateCounters();
                
                // Remove a animação após a transição
                setTimeout(() => {
                    statusBadge.classList.remove('status-animation');
                }, 500);
            }
        } catch (error) {
            console.error('Erro ao alterar status:', error);
            toastr.error('Erro ao alterar status');
        }
    }

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.status-toggle')) {
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });
    </script>
</body>
</html> 