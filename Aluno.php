<?php
// Assumindo que este arquivo é 'Aluno.php'
require_once __DIR__ . '/Conec.php';

// --- Processamento de Formulário (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';
  
  // Nomes das variáveis ajustados para melhor clareza com a tabela
  $matricula = trim($_POST['matricula'] ?? ''); 
  $nomeAluno = trim($_POST['nomeAluno'] ?? ''); 
  $curso = trim($_POST['curso'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $original = $_POST['original'] ?? ''; // Valor original da Matrícula para atualização

  // Ação Adicionar
  if ($acao === 'adicionar' && $matricula !== '' && $nomeAluno !== '' && $curso !== '' && $endereco !== '') {
    
    // SQL OK: Colunas: Matricula, nomeAluno, Curso, Endereco
    $stmt = $pdo->prepare("INSERT INTO Aluno (Matricula, nomeAluno, Curso, Endereco) VALUES (:m, :n, :c, :e)");
    
    // Tratamento de erro (opcional, mas recomendado)
    try {
        $stmt->execute([':m' => $matricula, ':n' => $nomeAluno, ':c' => $curso, ':e' => $endereco]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Código para erro de chave duplicada (geralmente 23000 no MySQL)
            header('Location: Aluno.php?erro=duplicidade'); 
            exit;
        }
        // Tratar outros erros de DB
        error_log("Erro ao inserir aluno: " . $e->getMessage()); 
    }
    
  }

  // Ação Atualizar
  if ($acao === 'atualizar' && $original !== '' && $matricula !== '') {
    // Note: A lógica de verificação de duplicidade é útil se a Matricula for alterada
    if ($original !== $matricula) {
      
      // *******************************************************************
      // FIX DEFINITIVO: USANDO TRANSAÇÕES E DESATIVANDO CHECAGEM DE FOREIGN KEY
      // *******************************************************************
      try {
        // Inicia a transação
        $pdo->beginTransaction();

        // NOVO: Desativa temporariamente a verificação de FKs
        // Isso permite a atualização da Matrícula na Nota antes do Aluno,
        // contornando o erro 1452.
        $pdo->exec('SET foreign_key_checks = 0');

        // 1. Verifica duplicidade (somente se a Matrícula for alterada)
        $chk = $pdo->prepare("SELECT 1 FROM Aluno WHERE Matricula = :m");
        $chk->execute([':m' => $matricula]);
        if ($chk->fetch()) {
          $pdo->rollBack(); // Reverte antes de redirecionar
          $pdo->exec('SET foreign_key_checks = 1'); // Garante que a checagem volte
          header('Location: Aluno.php?erro=duplicidade'); 
          exit;
        }

        // 2. PASSO 1: Atualiza a Matricula na tabela 'Nota' (tabela filha)
        $upNota = $pdo->prepare("UPDATE Nota SET Matricula=:m WHERE Matricula=:o");
        $upNota->execute([':m'=>$matricula, ':o'=>$original]);

        // 3. PASSO 2: Atualiza a Matrícula na tabela 'Aluno' (tabela pai)
        $up = $pdo->prepare("UPDATE Aluno SET Matricula=:m, nomeAluno=:n, Curso=:c, Endereco=:e WHERE Matricula=:o");
        $up->execute([':m'=>$matricula, ':n'=>$nomeAluno, ':c'=>$curso, ':e'=>$endereco, ':o'=>$original]);

        // Ativa novamente a verificação de FKs
        $pdo->exec('SET foreign_key_checks = 1');

        // Confirma as operações
        $pdo->commit();
        
        // Redireciona em caso de sucesso
        header('Location: Aluno.php');
        exit;

      } catch (PDOException $e) {
          // Se algo falhar, reverte as operações e trata o erro
          $pdo->rollBack();
          // Garante que a checagem de FKs seja reativada mesmo em caso de erro
          $pdo->exec('SET foreign_key_checks = 1'); 
          
          error_log("Erro durante a atualização transacional da Matrícula: " . $e->getMessage());
          
          // Volta ao modo de produção
          header('Location: Aluno.php?erro=atualizacao_falhou'); 
          exit;
      }

    } else {

      // Atualiza mantendo a Matrícula (Não precisa de transação)
      // SQL OK: SET nomeAluno, Curso, Endereco WHERE Matricula=:m
      $up = $pdo->prepare("UPDATE Aluno SET nomeAluno=:n, Curso=:c, Endereco=:e WHERE Matricula=:m");
      $up->execute([':n'=>$nomeAluno, ':c'=>$curso, ':e'=>$endereco, ':m'=>$matricula]);
      
      // Redireciona em caso de sucesso (matrícula não alterada)
      header('Location: Aluno.php');
      exit;
    }
  }
}

// --- Ação Excluir (GET) ---
if (($_GET['acao'] ?? '') === 'excluir') {
  $m = $_GET['matricula'] ?? '';
  if ($m !== '') {
    
    // *******************************************************************
    // FIX ANTERIOR: USANDO TRANSAÇÕES PARA GARANTIR A ATOMICIDADE DAS EXCLUSÕES
    // *******************************************************************
    
    try {
        // Inicia a transação
        $pdo->beginTransaction();

        // 1. Deleta as notas do aluno na tabela 'Nota' (tabela filha)
        $delNotas = $pdo->prepare("DELETE FROM Nota WHERE Matricula = :m");
        $delNotas->execute([':m' => $m]);
        
        // 2. Agora, deleta o aluno na tabela 'Aluno' (tabela pai)
        $del = $pdo->prepare("DELETE FROM Aluno WHERE Matricula = :m");
        $del->execute([':m' => $m]);

        // Confirma as operações
        $pdo->commit();
        
    } catch (PDOException $e) {
        // Se algo falhar, reverte as operações
        $pdo->rollBack();
        error_log("Erro durante a exclusão transacional: " . $e->getMessage());
        header('Location: Aluno.php?erro=exclusao_falhou');
        exit;
    }
  }
}


// --- Lógica para Edição ---
$editando = false;

// Nomes das chaves ajustados para a nova tabela
$alunoEdit = ['Matricula'=>'', 'nomeAluno'=>'', 'Curso'=>'', 'Endereco'=>'']; 
if (($_GET['acao'] ?? '') === 'editar') {
  $m = $_GET['matricula'] ?? ''; 
  if ($m !== '') {

    // SQL OK: SELECT Matricula, nomeAluno, Curso, Endereco
    $s = $pdo->prepare("SELECT Matricula, nomeAluno, Curso, Endereco FROM Aluno WHERE Matricula = :m");
    $s->execute([':m' => $m]);
    if ($row = $s->fetch(PDO::FETCH_ASSOC)) { $editando = true; $alunoEdit = $row; }
  }
}


// --- Leitura da Lista ---
// SQL OK: SELECT Matricula, nomeAluno, Curso, Endereco
$lista = $pdo->query("SELECT Matricula, nomeAluno, Curso, Endereco FROM Aluno ORDER BY nomeAluno")->fetchAll(PDO::FETCH_ASSOC);


$msg = '';
if (isset($_GET['erro']) && $_GET['erro']==='duplicidade') {
  $msg = '<div class="alert alert-error">Matrícula já existente.</div>';
}
// Tratamento de erro para falha na exclusão
if (isset($_GET['erro']) && $_GET['erro']==='exclusao_falhou') {
  $msg = '<div class="alert alert-error">Falha ao excluir o aluno. Verifique o log de erros.</div>';
}
// Novo tratamento de erro para falha na atualização de ID
if (isset($_GET['erro']) && $_GET['erro']==='atualizacao_falhou') {
  $msg = '<div class="alert alert-error">Falha ao atualizar a Matrícula do aluno. Verifique o log de erros.</div>';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>CRUD Simples de Alunos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css" >
</head>
<body>

<header>
  <div class="container">
    <h1>Alunos</h1>
    <nav><a href="Aluno.php" class="btn">Atualizar</a></nav> </div>
</header>

<main class="container">
  <?= $msg ?>

  <table class="table">
    <thead>
      <tr>
        <th>Matrícula</th>
        <th>Nome</th>
        <th>Curso</th>
        <th>Endereço</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($lista)): ?>
      <tr><td colspan="5">Nenhum aluno cadastrado.</td></tr> <?php else: ?>
      <?php foreach ($lista as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['Matricula']) ?></td>
          <td><?= htmlspecialchars($a['nomeAluno']) ?></td>
          <td><?= htmlspecialchars($a['Curso']) ?></td>
          <td><?= htmlspecialchars($a['Endereco']) ?></td>
          <td class="acao">
            <a class="btn btn-secondary" href="?acao=editar&matricula=<?= urlencode($a['Matricula']) ?>">Editar</a>
            <a class="btn btn-danger" href="?acao=excluir&matricula=<?= urlencode($a['Matricula']) ?>"
                onclick="return confirm('Excluir este aluno?');">Excluir</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  
  <hr>

  <form class="form-card" method="post">
    <h2><?= $editando ? 'Editar Aluno' : 'Adicionar Aluno' ?></h2>

    <?php if ($editando): ?>
      <input type="hidden" name="original" value="<?= htmlspecialchars($alunoEdit['Matricula']) ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="matricula">Matrícula </label>
        <input type="text" id="matricula" name="matricula" required
                value="<?= htmlspecialchars($alunoEdit['Matricula']) ?>">
      </div>
      <div class="form-group">
        <label for="nomeAluno">Nome do Aluno </label> 
        <input type="text" id="nomeAluno" name="nomeAluno" maxlength="100" required
                value="<?= htmlspecialchars($alunoEdit['nomeAluno']) ?>">
      </div>
      <div class="form-group">
  <label for="curso">Curso</label> 
  <select id="curso" name="curso" required>
    <option value="">Selecione...</option>
        <?php $cursoAtual = htmlspecialchars($alunoEdit['Curso']); ?>
    <option value="Informatica" <?= ($cursoAtual == 'Informatica') ? 'selected' : '' ?>>Técnico em Informática</option>
    <option value="Enfermagem" <?= ($cursoAtual == 'Enfermagem') ? 'selected' : '' ?>>Técnico em Enfermagem</option>
    <option value="Ensino Medio" <?= ($cursoAtual == 'Ensino Medio') ? 'selected' : '' ?>>Ensino Médio (Base)</option>
  </select>
</div>
        <div class="form-group">
          <label for="endereco">Endereço </label>
          <input type="text" id="endereco" name="endereco" maxlength="150" required
                value="<?= htmlspecialchars($alunoEdit['Endereco']) ?>">
        </div>
    </div>
    
    <div class="form-actions">
      <?php if ($editando): ?>
        <input type="hidden" name="acao" value="atualizar">
        <button class="btn" type="submit">Salvar Alterações</button>
        <a class="btn btn-secondary" href="Aluno.php">Cancelar</a> <?php else: ?>
        <input type="hidden" name="acao" value="adicionar">
        <button class="btn" type="submit">Adicionar</button>
      <?php endif; ?>
    </div>
  </form>
</main>

<footer>
  <div class="container">
    <small>&copy; <?= date('Y') ?> — Sistema Escola</small>
  </div>
</footer>
</body>
</html>