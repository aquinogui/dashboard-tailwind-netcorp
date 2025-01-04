<?php
// Salvar Configuração do Certificado
if (isset($_POST['salvar_configuracao'])) {
    $imagem_fundo = $_FILES['imagem_fundo']['name'];
    
    if (!empty($imagem_fundo)) {
        $destino_imagem = 'certificados/' . basename($imagem_fundo);
        move_uploaded_file($_FILES['imagem_fundo']['tmp_name'], $destino_imagem);
    } else {
        $destino_imagem = $config_certificado['imagem_fundo'] ?? null;
    }

    if (!$config_certificado) {
        $stmt_insert = $pdo->prepare("INSERT INTO configuracoes_certificado (imagem_fundo, evento_id) VALUES (?, ?)");
        $stmt_insert->execute([$destino_imagem, $evento_id]);
    } else {
        $stmt_update = $pdo->prepare("UPDATE configuracoes_certificado SET imagem_fundo = ? WHERE evento_id = ?");
        $stmt_update->execute([$destino_imagem, $evento_id]);
    }

    header("Location: inscritos_evento.php?id=$evento_id");
    exit();
}

// Enviar Certificados em Massa
if (isset($_POST['enviar_certificados'])) {
    foreach ($inscritos as $inscrito) {
        try {
            $certificado_pdf = gerarCertificado($inscrito['nome'], $config_certificado);
            $stmt = $pdo->prepare("UPDATE inscritos SET certificado = ?, certificado_enviado = 1, data_envio_certificado = NOW() WHERE id = ?");
            $stmt->execute([$certificado_pdf, $inscrito['id']]);

            $assunto = "Seu Certificado do evento: " . $evento['nome'];
            $mensagem = include 'templates/email_certificado.php';
            
            enviarEmail($inscrito['email'], $assunto, $mensagem, $certificado_pdf);
        } catch (Exception $e) {
            error_log("Erro ao processar certificado para {$inscrito['nome']}: " . $e->getMessage());
        }
    }
}

// Restante do processamento de ações... 