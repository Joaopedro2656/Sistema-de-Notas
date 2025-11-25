<?php
// Inclui o arquivo de conexão e inicia a sessão
include('Conec.php');
session_start();

$mensagem = "";

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_digitado = $_POST['usuario'];
    $senha_digitada = $_POST['senha'];

    // 1. Consulta SQL: Busca o usuário na tabela 'usuarios'.
    // Busca pelo campo 'usuario' (username) OU pelo campo 'id' (onde a Matrícula do aluno é armazenada).
    $sql = "SELECT 
                u.id, 
                u.usuario, 
                u.senha, 
                u.nome, 
                u.tipo_usuario,
                a.Matricula AS aluno_matricula
            FROM usuarios u
            LEFT JOIN aluno a ON u.id = a.Matricula
            WHERE u.usuario = :login_input OR u.id = :login_input_id";
    
    // 2. Prepara e executa a consulta
    try {
        // Assume que $pdo está disponível via Conec.php
        $stmt = $pdo->prepare($sql);
        // O mesmo valor digitado é usado para tentar logar como usuário (username) ou como ID/Matrícula.
        $stmt->execute([
            'login_input' => $usuario_digitado, 
            'login_input_id' => $usuario_digitado
        ]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Verifica a senha 
            if ($senha_digitada == $usuario['senha']) {
                
                // --- Login Bem-Sucedido: SALVANDO DADOS CRUCIAIS NA SESSÃO ---
                
                $_SESSION['logado'] = true;
                $_SESSION['nome_usuario'] = $usuario['nome'];
                $_SESSION['nivel_acesso'] = $usuario['tipo_usuario']; 

                // Lógica CRÍTICA: Define o ID da sessão. 
                // Se for aluno E a Matrícula foi encontrada, usa a Matrícula.
                // Caso contrário (Professor ou Matrícula não encontrada), usa o ID do usuário.
                if ($usuario['tipo_usuario'] === 'aluno' && !empty($usuario['aluno_matricula'])) {
                    // Este valor será usado no BoletimAluno.php (WHERE Matricula = :mat)
                    $_SESSION['id_usuario'] = $usuario['aluno_matricula'];
                } else {
                    $_SESSION['id_usuario'] = $usuario['id'];
                }

                // -------------------------------------------------------------
                // LÓGICA DE REDIRECIONAMENTO ATUALIZADA
                // -------------------------------------------------------------
                
                // Se o tipo_usuario for 'professor'
                if ($_SESSION['nivel_acesso'] == 'professor') {
                    header("Location: pagiiniprof.php");
                    exit();
                } 
                // Se for 'aluno' (mantém o redirecionamento original)
                elseif ($_SESSION['nivel_acesso'] == 'aluno') {
                    header("Location: paginialuno.php");
                    exit();
                }
                // Outros níveis de acesso ou um fallback (ajuste conforme necessário)
                else {
                    header("Location: index.php"); 
                    exit();
                }
                
                // -------------------------------------------------------------
                
            } else {
                // Senha incorreta
                $mensagem = "Senha incorreta.";
            }
        } else {
            // Usuário não encontrado
            $mensagem = "Usuário não encontrado.";
        }
    } catch (PDOException $e) {
        // Captura e exibe erro de conexão ou SQL
        $mensagem = "Erro interno do sistema. Tente novamente mais tarde.";
        error_log("Erro de Login: " . $e->getMessage()); 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Notas</title>
    <style>
        :root {
            --cor-principal: #1f50a9;
            --cor-fundo: #173873;
            --cor-clara: #ffffff;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--cor-fundo);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: var(--cor-principal);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            width: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* LOGO SN */
        .logo-area {
            background-color: var(--cor-principal);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            background-color: var(--cor-principal);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--cor-clara);
            font-size: 40px;
            font-weight: bold;
            border: 2px solid var(--cor-clara);
            line-height: 0.8;
            padding-top: 5px;
            position: relative;
        }
        
        .logo::after {
            content: "SISTEMA DE NOTAS";
            position: absolute;
            font-size: 8px;
            bottom: -5px; 
            letter-spacing: 1px;
            color: var(--cor-clara);
        }

        /* Campos de input */
        .input-group {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;
        }

        .input-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #555;
            font-size: 18px;
        }

        /* Botão de Login */
        .login-submit {
            margin-top: 10px;
            width: 100%;
            border: none;
            background: var(--cor-clara);
            color: var(--cor-principal);
            font-size: 16px;
            font-weight: bold;
            padding: 12px 0;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-submit:hover {
            background-color: #f0f0f0;
        }

        /* Mensagem de erro/feedback */
        .mensagem-erro {
            color: #ffdddd;
            margin-top: 15px;
            padding: 5px 10px;
            background-color: rgba(255, 0, 0, 0.4);
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        
        <div class="logo-area">
            <div class="logo">N N</div>
        </div>

        <form method="POST" action="login.php">
            <div class="input-group">
                <i class="icon fas fa-user"></i>
                <input type="text" name="usuario" placeholder="USER ou MATRÍCULA" required>
            </div>
            
            <div class="input-group">
                <i class="icon fas fa-lock"></i>
                <input type="password" name="senha" placeholder="PASSWORD" required>
            </div>
            
            <button type="submit" class="login-submit">
                LOGIN
            </button>
        </form>

        <?php
        // Exibe a mensagem de erro/feedback
        if (!empty($mensagem)) {
            echo '<p class="mensagem-erro">' . $mensagem . '</p>';
        }
        ?>

    </div>
</body>
</html>