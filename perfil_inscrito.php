<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Participante</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <style>
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-hover:hover {
            animation: pulse 1s infinite;
        }
        .sidebar-expanded {
            width: 240px;
        }
        .sidebar-collapsed {
            width:85px;
        }
        .sidebar-transition {
            transition: width 0.3s ease;
        }
        .content-transition {
            transition: margin-left 0.3s ease;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .slide-in {
            animation: slideIn 0.5s ease-in-out;
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 bg-gray-800 shadow-xl sidebar-transition sidebar-collapsed z-50">
        <div class="flex flex-col h-full">
            <!-- Logo Area -->
            <div class="flex items-center justify-center h-16 border-b border-gray-700">
                <i class='bx bx-user-circle text-3xl text-blue-400'></i>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 pt-4">
                <div class="sidebar-item group active">
                    <a href="#" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white relative group">
                        <i class='bx bx-grid-alt text-2xl mb-1'></i>
                        <span class="text-xs sidebar-text">Dashboard</span>
                    </a>
                </div>

                <div class="sidebar-item group">
                    <a href="#" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white relative group">
                        <i class='bx bx-calendar-event text-2xl mb-1'></i>
                        <span class="text-xs sidebar-text text-center">Meus Eventos</span>
                    </a>
                </div>

                <div class="sidebar-item group">
                    <a href="#" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white relative group">
                        <i class='bx bx-certification text-2xl mb-1'></i>
                        <span class="text-xs sidebar-text">Certificados</span>
                    </a>
                </div>

                <div class="sidebar-item group">
                    <a href="#" class="flex flex-col items-center px-2 py-3 text-gray-300 hover:bg-gray-700 hover:text-white relative group">
                        <i class='bx bx-message-square-dots text-2xl mb-1'></i>
                        <span class="text-xs sidebar-text">Mensagens</span>
                    </a>
                </div>
            </nav>

            <!-- Bottom Section -->
            <div class="border-t border-gray-700 p-4">
                <div class="sidebar-item group">
                    <a href="#" class="flex flex-col items-center text-gray-300 hover:bg-gray-700 hover:text-white relative group rounded-lg p-2">
                        <i class='bx bx-cog text-2xl mb-1'></i>
                        <span class="text-xs sidebar-text">Configurações</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="ml-[70px] content-transition">
        <!-- Top Navigation -->
        <nav class="bg-gray-800 shadow-lg sticky top-0 z-40">
            <div class="mx-auto px-4">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="text-gray-400 hover:text-white">
                            <i class='bx bx-menu text-2xl'></i>
                        </button>
                        <div class="ml-4">
                            <h1 class="text-xl font-bold text-white">Dashboard do Participante</h1>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button id="notifications-btn" class="relative p-2 text-gray-400 hover:text-white">
                            <i class='bx bx-bell text-xl'></i>
                            <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-500"></span>
                        </button>

                        <!-- User Menu -->
                        <div class="relative group">
                            <button class="flex items-center space-x-3">
                                <img class="h-8 w-8 rounded-full border-2 border-blue-400" src="https://via.placeholder.com/150" alt="User avatar">
                                <span class="hidden md:block text-sm font-medium text-gray-300">Maria Silva</span>
                                <i class='bx bx-chevron-down text-gray-400'></i>
                            </button>
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-xl py-2 hidden group-hover:block">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                    <i class='bx bx-user mr-2'></i>Perfil
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                    <i class='bx bx-cog mr-2'></i>Configurações
                                </a>
                                <div class="border-t border-gray-700 my-1"></div>
                                <a href="#" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700">
                                    <i class='bx bx-log-out mr-2'></i>Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="p-6">
            <!-- Quick Actions -->
            <div class="mb-8 fade-in">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="showUpcomingEvents()" class="flex items-center justify-center p-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors animate-hover">
                        <i class='bx bx-calendar mr-2 text-xl'></i>
                        <span>Próximos Eventos</span>
                    </button>
                    <button onclick="showCertificates()" class="flex items-center justify-center p-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors animate-hover">
                        <i class='bx bx-certification mr-2 text-xl'></i>
                        <span>Meus Certificados</span>
                    </button>
                    <button onclick="showMessages()" class="flex items-center justify-center p-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors animate-hover">
                        <i class='bx bx-envelope mr-2 text-xl'></i>
                        <span>Mensagens</span>
                    </button>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Eventos Confirmados -->
                <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden fade-in mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class='bx bx-calendar-check text-blue-400 mr-2'></i>
                            Eventos Confirmados
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php if (!empty($eventos)): ?>
                                <?php foreach ($eventos as $evento): ?>
                                    <div class="bg-gray-700 rounded-lg overflow-hidden hover:shadow-xl transition-all duration-300">
                                        <div class="relative">
                                            <div class="h-48 bg-blue-500 bg-opacity-20 flex items-center justify-center">
                                                <i class='bx bx-calendar-event text-6xl text-blue-400'></i>
                                            </div>
                                            <div class="absolute top-4 right-4 px-3 py-1 bg-green-500 text-white rounded-full text-sm">
                                                Confirmado
                                            </div>
                                        </div>
                                        <div class="p-4">
                                            <h4 class="font-semibold text-lg mb-2"><?= htmlspecialchars($evento['evento_nome']) ?></h4>
                                            <p class="text-gray-400 text-sm mb-3">
                                                <i class='bx bx-calendar mr-2'></i>
                                                <?= date('d/m/Y', strtotime($evento['data_evento'])) ?>
                                            </p>
                                            <div class="flex justify-between items-center">
                                                <button onclick="viewEventDetails(<?= $evento['evento_id'] ?>)" 
                                                    class="text-blue-400 hover:text-blue-300 transition-colors">
                                                    <i class='bx bx-info-circle mr-1'></i>
                                                    Detalhes
                                                </button>
                                                <?php if (!empty($certificados[$evento['evento_id']])): ?>
                                                    <span class="text-green-400 flex items-center">
                                                        <i class='bx bx-certification mr-1'></i>
                                                        Certificado Disponível
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-3 bg-gray-700 rounded-lg p-6 text-center">
                                    <i class='bx bx-calendar-x text-4xl text-gray-400 mb-2'></i>
                                    <p class="text-gray-400">Nenhum evento confirmado encontrado.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Certificados -->
                <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden fade-in mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class='bx bx-certification text-green-400 mr-2'></i>
                            Meus Certificados
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($eventos as $evento): ?>
                                <?php if (!empty($certificados[$evento['evento_id']])): ?>
                                    <?php foreach ($certificados[$evento['evento_id']] as $certificado): ?>
                                        <?php
                                        $nomeAluno = htmlspecialchars($inscrito['nome']);
                                        $eventoNome = htmlspecialchars($evento['evento_nome']);
                                        $pdfFileName = strtolower(str_replace([' ', '/'], ['_', ''], $nomeAluno . '_' . $eventoNome)) . '_certificado.pdf';
                                        $pdfFilePath = "certificados/" . $pdfFileName;

                                        if (!file_exists($pdfFilePath)) {
                                            try {
                                                $pdfFilePath = gerarCertificado($inscrito['nome'], $evento['evento_nome'], $certificado);
                                            } catch (Exception $e) {
                                                continue;
                                            }
                                        }
                                        ?>
                                        <div class="bg-gray-700 rounded-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                                            <!-- Preview do Certificado -->
                                            <div class="relative">
                                                <iframe src="<?= htmlspecialchars($pdfFilePath) ?>" 
                                                    class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105"
                                                    title="Visualização do Certificado">
                                                </iframe>
                                                <!-- Overlay com ações -->
                                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                    <div class="space-x-3">
                                                        <a href="<?= htmlspecialchars($pdfFilePath) ?>" 
                                                            download
                                                            class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                            <i class='bx bx-download mr-2'></i>
                                                            Download
                                                        </a>
                                                        <?php
                                                        $certificadoUrl = urlencode("https://network.corpbusiness.com.br/" . $pdfFilePath);
                                                        $shareText = urlencode("Participei do evento " . $evento['evento_nome'] . " e gostaria de compartilhar meu certificado!");
                                                        ?>
                                                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $certificadoUrl ?>&title=<?= $shareText ?>" 
                                                            target="_blank"
                                                            class="inline-flex items-center px-3 py-2 bg-[#0A66C2] text-white rounded-lg hover:bg-[#004182] transition-colors">
                                                            <i class='bx bxl-linkedin mr-2'></i>
                                                            LinkedIn
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                <h4 class="font-semibold text-lg mb-2"><?= htmlspecialchars($evento['evento_nome']) ?></h4>
                                                <p class="text-sm text-gray-400 mb-3">
                                                    <?= htmlspecialchars($certificado['nome_palestrante']) ?>
                                                </p>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-green-400 flex items-center text-sm">
                                                        <i class='bx bx-check-circle mr-1'></i>
                                                        Validado
                                                    </span>
                                                    <button onclick="previewCertificate('<?= htmlspecialchars($pdfFilePath) ?>')" 
                                                        class="text-blue-400 hover:text-blue-300 transition-colors">
                                                        <i class='bx bx-fullscreen mr-1'></i>
                                                        Visualizar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toastr configuration
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }

        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarTexts = document.querySelectorAll('.sidebar-text');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-expanded');
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('ml-[240px]');
            mainContent.classList.toggle('ml-[70px]');
            sidebarTexts.forEach(text => text.classList.toggle('hidden'));
        });

        // Notifications
        const notificationsBtn = document.getElementById('notifications-btn');
        notificationsBtn.addEventListener('click', () => {
            toastr.info('Você tem 2 novas notificações.', 'Notificações');
        });

        // Quick Actions
        function showUpcomingEvents() {
            toastr.info('Carregando próximos eventos...', 'Próximos Eventos');
        }

        function showCertificates() {
            toastr.info('Carregando seus certificados...', 'Meus Certificados');
        }

        function showMessages() {
            toastr.info('Carregando suas mensagens...', 'Mensagens');
        }

        // Event Actions
        function viewEventDetails(eventId) {
            toastr.info(`Visualizando detalhes do evento ${eventId}`, 'Detalhes do Evento');
        }

        function cancelEventRegistration(eventId) {
            toastr.warning(`Tem certeza que deseja cancelar sua inscrição no evento ${eventId}?`, 'Cancelar Inscrição', {
                closeButton: true,
                timeOut: 0,
                extendedTimeOut: 0,
                onclick: function() {
                    toastr.success(`Inscrição no evento ${eventId} cancelada com sucesso.`, 'Sucesso');
                }
            });
        }

        // Certificate Actions
        function downloadCertificate(certId) {
            toastr.success(`Iniciando download do certificado ${certId}...`, 'Download Iniciado');
        }

        function shareCertificate(certId) {
            toastr.info(`Compartilhando certificado ${certId}...`, 'Compartilhar Certificado');
        }
    </script>
</body>
</html>