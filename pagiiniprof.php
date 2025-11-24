<?php
// Inclui a conexão e verifica o status de login
include('Conec.php');
session_start();

// Se o usuário não estiver logado, redireciona para a página de login (login.php)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit();
}

// Assumindo que o nome do professor está armazenado na sessão
$nome_professor = $_SESSION['nome_usuario']; 

// Lógica de Sair da Conta (Logout)
if (isset($_GET['logout'])) {
    session_destroy();
    // Redireciona para a página de login (login.php)
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Professor - Espaço SN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cor-sidebar: #1f50a9;
            --cor-sidebar-hover: #173873;
            --cor-topo: #1f50a9;
            --cor-principal: #f0f0f0;
            --cor-texto-claro: #ffffff;
            --cor-botao-principal: #3a7bd5; /* Azul um pouco mais claro para botões de ação */
            --cor-sombra: rgba(0,0,0,0.1);
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
        }

        /* Sidebar - Lado Esquerdo (Estrutura Fornecida) */
        .sidebar {
            width: 250px;
            background-color: var(--cor-sidebar);
            color: var(--cor-texto-claro);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 5px var(--cor-sombra);
        }

        .logo-header {
            display: flex;
            align-items: center;
            padding: 0 20px 20px 20px;
            width: 100%;
            box-sizing: border-box;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        /* Adaptado para exibir um ícone padrão, pois o caminho da imagem não é acessível */
        .logo-icon {
            font-size: 28px;
            margin-right: 10px;
        }
        
        .espaco-sn {
            font-size: 20px;
            font-weight: bold;
        }

        /* Área de Perfil */
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

        /* Menu de Navegação */
        .nav-menu {
            width: 100%;
            flex-grow: 1; /* Permite que o menu ocupe o espaço restante */
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

        .nav-menu a:hover, .nav-menu a.active {
            background-color: var(--cor-sidebar-hover);
        }
        
        .nav-menu a i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Botão de Sair */
        .logout-link {
            padding: 20px;
            text-align: center;
            color: var(--cor-texto-claro);
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
            background-color: #d9534f; /* Vermelho para destaque */
            transition: background-color 0.3s;
        }
        
        .logout-link:hover {
            background-color: #c9302c;
        }


        /* Conteúdo Principal - Lado Direito (Estrutura Fornecida) */
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
            box-shadow: 0 2px 5px var(--cor-sombra);
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
            min-height: 80vh; /* Ajustado para melhor visualização */
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Estilos dos Botões do Dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .dashboard-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            background-color: #f9f9f9;
            border: 2px solid #e0e0e0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-align: center;
            min-height: 150px;
        }

        .dashboard-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
            border-color: var(--cor-botao-principal);
        }
        
        .dashboard-button i {
            font-size: 45px;
            margin-bottom: 10px;
            color: var(--cor-botao-principal);
            transition: color 0.3s;
        }

        .dashboard-button:nth-child(2) i { color: #5cb85c; } /* Verde para Notas */
        .dashboard-button:nth-child(3) i { color: #f0ad4e; } /* Amarelo para Professores */
        .dashboard-button:nth-child(4) i { color: #d9534f; } /* Vermelho para Boletim */

        .button-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .button-subtitle {
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-header">
            <!-- Usando ícone padrão, pois o caminho da imagem não é acessível -->
            <i class="fas fa-graduation-cap logo-icon"></i> 
            <div class="espaco-sn">Espaço SN</div>
        </div>
        
        <div class="profile-area">
            <div class="profile-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="welcome-text">
                Olá, <?php echo htmlspecialchars($nome_professor); ?><br>
                Painel do Professor
            </div>
        </div>

        <nav class="nav-menu">
            <a href="pagiiniprof.php" class="active"><i class="fas fa-home"></i> Página inicial</a>
            <!-- Links de navegação do Professor, embora os principais estejam no conteúdo -->
            <a href="Nota.php"><i class="fas fa-pencil-alt"></i> Lançar Notas</a>
            <a href="BoletimTurma.php"><i class="fas fa-clipboard-list"></i> Boletim de Turma</a>
        </nav>
        
        <!-- O link de logout precisa apontar para este mesmo arquivo com o parâmetro 'logout=true' -->
        <a href="pagiiniprof.php?logout=true" class="logout-link">sair da conta</a>
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
                <h2>Acesso Rápido - Funções do Professor</h2>
                <p>Clique em uma das opções abaixo para gerenciar alunos, notas e relatórios.</p>

                <!-- Grade de Botões de Acesso Rápido -->
                <div class="dashboard-grid">

                    <!-- Botão 1: Gestão de Alunos (Aluno.php) -->
                    <a href="Aluno.php" class="dashboard-button">
                        <i class="fas fa-users"></i>
                        <span class="button-title">Gestão de Alunos</span>
                        <span class="button-subtitle">Adicionar, editar e visualizar cadastros.</span>
                    </a>

                    <!-- Botão 2: Lançamento de Notas (Nota.php) -->
                    <a href="Nota.php" class="dashboard-button">
                        <i class="fas fa-edit"></i>
                        <span class="button-title">Lançamento de Notas</span>
                        <span class="button-subtitle">Inserir e atualizar notas das disciplinas.</span>
                    </a>

                    <!-- Botão 3: Gestão de Professores (Professor.php) -->
                    <a href="Professor.php" class="dashboard-button">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span class="button-title">Cadastro de Professores</span>
                        <span class="button-subtitle">Gerenciar dados e atribuições docentes.</span>
                    </a>

                    <!-- Botão 4: Emissão de Boletim (BoletimTurma.php) -->
                    <a href="BoletimTurma.php" class="dashboard-button">
                        <i class="fas fa-file-invoice"></i>
                        <span class="button-title">Boletins da Turma</span>
                        <span class="button-subtitle">Gerar e consultar relatórios de desempenho.</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>