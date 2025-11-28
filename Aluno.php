<?php

require_once __DIR__ . '/Conec.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';
  
  $matricula = trim($_POST['matricula'] ?? ''); 
  $nomeAluno = trim($_POST['nomeAluno'] ?? ''); 
  $curso = trim($_POST['curso'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $original = $_POST['original'] ?? '';

  if ($acao === 'adicionar' && $matricula !== '' && $nomeAluno !== '' && $curso !== '' && $endereco !== '') {
    $stmt = $pdo->prepare("INSERT INTO Aluno (Matricula, nomeAluno, Curso, Endereco) VALUES (:m, :n, :c, :e)");
    try {
        $stmt->execute([':m' => $matricula, ':n' => $nomeAluno, ':c' => $curso, ':e' => $endereco]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
            header('Location: Aluno.php?erro=duplicidade'); 
            exit;
        }
        error_log("Erro ao inserir aluno: " . $e->getMessage()); 
    }
  }

  if ($acao === 'atualizar' && $original !== '' && $matricula !== '') {
    if ($original !== $matricula) {
      try {
        $pdo->beginTransaction();
        $pdo->exec('SET foreign_key_checks = 0');

        $chk = $pdo->prepare("SELECT 1 FROM Aluno WHERE Matricula = :m");
        $chk->execute([':m' => $matricula]);
        if ($chk->fetch()) {
          $pdo->rollBack();
          $pdo->exec('SET foreign_key_checks = 1');
          header('Location: Aluno.php?erro=duplicidade'); 
          exit;
        }

        $upNota = $pdo->prepare("UPDATE Nota SET Matricula=:m WHERE Matricula=:o");
        $upNota->execute([':m'=>$matricula, ':o'=>$original]);

        $up = $pdo->prepare("UPDATE Aluno SET Matricula=:m, nomeAluno=:n, Curso=:c, Endereco=:e WHERE Matricula=:o");
        $up->execute([':m'=>$matricula, ':n'=>$nomeAluno, ':c'=>$curso, ':e'=>$endereco, ':o'=>$original]);

        $pdo->exec('SET foreign_key_checks = 1');
        $pdo->commit();
        
        header('Location: Aluno.php');
        exit;

      } catch (PDOException $e) {
          $pdo->rollBack();
          $pdo->exec('SET foreign_key_checks = 1'); 
          error_log("Erro durante a atualização transacional da Matrícula: " . $e->getMessage());
          header('Location: Aluno.php?erro=atualizacao_falhou'); 
          exit;
      }

    } else {

      $up = $pdo->prepare("UPDATE Aluno SET nomeAluno=:n, Curso=:c, Endereco=:e WHERE Matricula=:m");
      $up->execute([':n'=>$nomeAluno, ':c'=>$curso, ':e'=>$endereco, ':m'=>$matricula]);
      
      header('Location: Aluno.php');
      exit;
    }
  }
}

if (($_GET['acao'] ?? '') === 'excluir') {
  $m = $_GET['matricula'] ?? '';
  if ($m !== '') {
    try {
        $pdo->beginTransaction();

        $delNotas = $pdo->prepare("DELETE FROM Nota WHERE Matricula = :m");
        $delNotas->execute([':m' => $m]);
        
        $del = $pdo->prepare("DELETE FROM Aluno WHERE Matricula = :m");
        $del->execute([':m' => $m]);

        $pdo->commit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro durante a exclusão transacional: " . $e->getMessage());
        header('Location: Aluno.php?erro=exclusao_falhou');
        exit;
    }
  }
}

$editando = false;

$alunoEdit = ['Matricula'=>'', 'nomeAluno'=>'', 'Curso'=>'', 'Endereco'=>'']; 
if (($_GET['acao'] ?? '') === 'editar') {
  $m = $_GET['matricula'] ?? ''; 
  if ($m !== '') {
    $s = $pdo->prepare("SELECT Matricula, nomeAluno, Curso, Endereco FROM Aluno WHERE Matricula = :m");
    $s->execute([':m' => $m]);
    if ($row = $s->fetch(PDO::FETCH_ASSOC)) { $editando = true; $alunoEdit = $row; }
  }
}

$lista = $pdo->query("SELECT Matricula, nomeAluno, Curso, Endereco FROM Aluno ORDER BY nomeAluno")->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
if (isset($_GET['erro']) && $_GET['erro']==='duplicidade') {
  $msg = '<div class="alert alert-error">Matrícula já existente.</div>';
}
if (isset($_GET['erro']) && $_GET['erro']==='exclusao_falhou') {
  $msg = '<div class="alert alert-error">Falha ao excluir o aluno. Verifique o log de erros.</div>';
}
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
      <tr><td colspan="5">Nenhum aluno cadastrado.</td></tr> 
    <?php else: ?>
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
        <a class="btn btn-secondary" href="Aluno.php">Cancelar</a> 
      <?php else: ?>
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