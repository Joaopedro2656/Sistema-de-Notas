<?php
include('Conec.php');
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit();
}

$nome_aluno = $_SESSION['nome_usuario'];

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espaço SN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cor-sidebar: #1f50a9;
            --cor-sidebar-hover: #173873;
            --cor-topo: #1f50a9;
            --cor-principal: #f0f0f0;
            --cor-texto-claro: #ffffff;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--cor-sidebar);
            color: var(--cor-texto-claro);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-header {
            display: flex;
            align-items: center;
            padding: 0 20px 20px 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .logo-circle {
            width: 40px;
            height: 40px;
            background-color: var(--cor-sidebar);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--cor-texto-claro);
            font-size: 20px;
            font-weight: bold;
            border: 2px solid var(--cor-texto-claro);
            margin-right: 10px;
        }

        .logo-circle::after {
            content: "N N";
        }
        
        .espaco-sn {
            font-size: 18px;
            font-weight: bold;
        }

        .profile-area {
            background-color: var(--cor-sidebar-hover);
            width: 100%;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background-color: var(--cor-sidebar);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--cor-texto-claro);
            font-size: 40px;
            border: 3px solid var(--cor-texto-claro);
            margin: 0 auto 10px;
        }
        
        .welcome-text {
            line-height: 1.4;
        }

        .nav-menu {
            width: 100%;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            text-decoration: none;
            color: var(--cor-texto-claro);
            background-color: var(--cor-sidebar);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
        }

        .nav-menu a:hover {
            background-color: var(--cor-sidebar-hover);
        }
        
        .nav-menu a i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .logout-link {
            margin-top: auto;
            padding: 20px;
            text-align: center;
            color: var(--cor-texto-claro);
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
            background-color: var(--cor-sidebar-hover);
        }
        
        .logout-link:hover {
            background-color: #ff0000;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--cor-principal);
        }

        .header-bar {
            background-color: var(--cor-topo);
            color: var(--cor-texto-claro);
            padding: 10px 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 40px;
        }
        
        .notifications {
            display: flex;
            align-items: center;
            font-size: 16px;
        }
        
        .notifications i {
            margin-left: 10px;
            font-size: 20px;
        }

        .page-content {
            padding: 20px;
            flex-grow: 1;
            background-color: var(--cor-principal); 
        }
        
        .content-box {
            background-color: #ffffff;
            border: 1px solid #ddd;
            min-height: 600px;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-header">
            <div class=""><img src="\\servidormedio\2310872\6 periodo(ultima pasta)\trabalho final\logo notas\Capturar.PNG"></div>
            <div class="espaco-sn">Espaço SN</div>
        </div>
        
        <div class="profile-area">
            <div class="profile-icon"><i class="fas fa-user"></i></div>
            <div class="welcome-text">
                Olá, <?php echo htmlspecialchars($nome_aluno); ?><br>
                Bem vindo de volta!
            </div>
        </div>

        <nav class="nav-menu">
            <a href="#"><i class="fas fa-home"></i> Página inicial</a>
            <a href="boletimaluno_novo.php"><i class="fas fa-file-alt"></i> Meu Boletim</a>
            <a href="#"><i class="fas fa-question-circle"></i> Dúvidas</a>
        </nav>
        
        <a href="paginialuno.php?logout=true" class="logout-link">sair da conta</a>
    </div>

    <div class="main-content">
        <header class="header-bar">
            <div class="notifications">
                notificações
                <i class="fas fa-bell"></i>
            </div>
        </header>
        <div class="page-content">
            <div class="content-box">
                <h2>Bem-vindo ao Sistema de Notas!</h2>
                <p>Esta é a área principal após o login.</p>
            </div>
        </div>
    </div>
</body>
</html>