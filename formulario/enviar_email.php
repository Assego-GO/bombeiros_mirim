<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'listar_matricula.php';

$dados = file_get_contents("php://input");

$data = json_decode($dados, true);

$numero_matricula = $data['matricula'];
$email = $data['email'];

$resposta = buscarMatriculaAluno($numero_matricula);


$nome_aluno = $resposta['nome_aluno'];
    
$data_matricula = $resposta['data_matricula'];
$status_matricula = $resposta['status_matricula'];
$nome_turma = $resposta['nome_turma'];
$nome_unidade = $resposta['nome_unidade'];
$coordenador = $resposta['coordenador'];
$telefone_unidade = $resposta['telefone'];
$endereco_unidade = $resposta['endereco'];




$mail = new PHPMailer(true);

try {
   
    $mail->isSMTP();
    $mail->Host = 'mail.assego.com.br';
    $mail->SMTPAuth = true;
    $mail->Username = 'superacao@assego.com.br';
    $mail->Password = 'rotam102030';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    // Remetente e destinatário
    $mail->setFrom('superacao@assego.com.br', 'Superação');
    $mail->addAddress($email, $nome_aluno);
    

    
    

    
    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Matrícula realizada!';


    $template = file_get_contents("template-inline.html");

 



    $template = str_replace('{NOME_DO_ALUNO}', $nome_aluno, $template);
    $template = str_replace('{NUMERO_MATRICULA}', $numero_matricula, $template);
    $template = str_replace('{UNIDADE}', $nome_unidade, $template);
    $template = str_replace('{TURMA}', $nome_turma, $template);
    $template = str_replace('{DATA_INICIO}', $data_matricula, $template);
    $template = str_replace('{NOME_COORDENADOR}', $coordenador, $template);
    $template = str_replace('{TELEFONE_UNIDADE}', $telefone_unidade, $template);
    
    // Corpo do e-mail com imagem incorporada
    $mail->Body = $template;
    
    $mail->AltBody = 'Este é um e-mail de teste com imagem anexada. Se você está vendo esta mensagem, seu cliente de e-mail não suporta HTML.';
    
   
   
    
    $mail->send();
    echo json_encode([
        'mensagem' => 'Email enviado:', $mail->ErrorInfo,
        'resposta' => $resposta
    ]);
} catch (Exception $e) {
    echo json_encode([
        'mensagem' => 'Erro ao enviar email:', $mail->ErrorInfo,
        'matricula' => 'Matricula: ', $numero_matricula
    ]);
}
?>
