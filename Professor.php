<?php
require_once __DIR__ . '/Conec.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';
  $idProfessor = (int)trim($_POST['idProfessor'] ?? '');
  $disciplina = trim($_POST['disciplina'] ?? '');
  $nomeProfessor = trim($_POST['nomeProfessor'] ?? '');
  $original = (int)($_POST['original'] ?? '');

  if ($acao === 'adicionar' && $idProfessor > 0 && $disciplina !== '' && $nomeProfessor !== '') {
    $stmt = $pdo->prepare("INSERT INTO Professor (ID_Professor, nomeProfessor, Disciplina) VALUES (:id, :n, :d)");
    try {
        $stmt->execute([':id' => $idProfessor, ':n' => $nomeProfessor, ':d' => $disciplina]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
            header('Location: Professor.php?erro=duplicidade'); 
            exit;
        }
    }
  }

  if ($acao === 'atualizar' && $original > 0 && $idProfessor > 0 && $nomeProfessor !== '') {
    if ($original !== $idProfessor) {
      $chk = $pdo->prepare("SELECT 1 FROM Professor WHERE ID_Professor = :id");
      $chk->execute([':id' => $idProfessor]);
      if ($chk->fetch()) {
        header('Location: Professor.php?erro=duplicidade');
        exit;
      }
      $up = $pdo->prepare("UPDATE Professor SET ID_Professor=:id, nomeProfessor=:n, Disciplina=:d WHERE ID_Professor=:o");
      $up->execute([':id'=>$idProfessor, ':n'=>$nomeProfessor, ':d'=>$disciplina, ':o'=>$original]);
    } else {
      $up = $pdo->prepare("UPDATE Professor SET nomeProfessor=:n, Disciplina=:d WHERE ID_Professor=:id");
      $up->execute([':n'=>$nomeProfessor, ':d'=>$disciplina, ':id'=>$idProfessor]);
    }
  }
}

if (($_GET['acao'] ?? '') === 'excluir') {
  $id = (int)($_GET['idProfessor'] ?? 0);
  if ($id > 0) {
    $del = $pdo->prepare("DELETE FROM Professor WHERE ID_Professor = :id");
    $del->execute([':id' => $id]);
  }
}

$editando = false;
$professorEdit = ['ID_Professor'=>'', 'nomeProfessor'=>'', 'Disciplina'=>''];

if (($_GET['acao'] ?? '') === 'editar') {
  $id = (int)($_GET['idProfessor'] ?? 0);
  if ($id > 0) {
    $s = $pdo->prepare("SELECT ID_Professor, nomeProfessor, Disciplina FROM Professor WHERE ID_Professor = :id");
    $s->execute([':id' => $id]);
    if ($row = $s->fetch(PDO::FETCH_ASSOC)) { 
      $editando = true; 
      $professorEdit = $row; 
    }
  }
}

$lista = $pdo->query("SELECT ID_Professor, nomeProfessor, Disciplina FROM Professor ORDER BY nomeProfessor")->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
if (isset($_GET['erro']) && $_GET['erro']==='duplicidade') {
  $msg = '<div class="alert alert-error">ID do Professor já existente.</div>';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>CRUD Simples de Professores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="container">
    <h1>Professores</h1>
    <nav><a href="Professor.php" class="btn">Atualizar Lista</a></nav>
  </div>
</header>

<main class="container">
  <?= $msg ?>

  <h2>Lista de Professores</h2>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Disciplina</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($lista)): ?>
      <tr><td colspan="4">Nenhum professor cadastrado.</td></tr> 
    <?php else: ?>
      <?php foreach ($lista as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['ID_Professor']) ?></td>
          <td><?= htmlspecialchars($p['nomeProfessor']) ?></td>
          <td><?= htmlspecialchars($p['Disciplina']) ?></td>
          <td class="acao">
            <a class="btn btn-secondary" href="?acao=editar&idProfessor=<?= urlencode($p['ID_Professor']) ?>">Editar</a>
            <a class="btn btn-danger" href="?acao=excluir&idProfessor=<?= urlencode($p['ID_Professor']) ?>"
                onclick="return confirm('Excluir este professor?');">Excluir</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  
  <form class="form-card" method="post">
    <h2><?= $editando ? 'Editar Professor' : 'Adicionar Professor' ?></h2>

    <?php if ($editando): ?>
      <input type="hidden" name="original" value="<?= htmlspecialchars($professorEdit['ID_Professor']) ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="idProfessor">ID Professor</label>
        <input type="number" id="idProfessor" name="idProfessor" required
                value="<?= htmlspecialchars($professorEdit['ID_Professor']) ?>"
                <?= $editando ? 'readonly' : '' ?>>
      </div>

      <div class="form-group">
        <label for="nomeProfessor">Nome</label>
        <input type="text" id="nomeProfessor" name="nomeProfessor" maxlength="100" required
                value="<?= htmlspecialchars($professorEdit['nomeProfessor']) ?>">
      </div>

      <div class="form-group">
        <label for="disciplina">Disciplina</label>
        <input type="text" id="disciplina" name="disciplina" maxlength="50" required
            value="<?= htmlspecialchars($professorEdit['Disciplina']) ?>">
      </div>
    </div>
    
    <div class="form-actions">
      <?php if ($editando): ?>
        <input type="hidden" name="acao" value="atualizar">
        <button class="btn" type="submit">Salvar Alterações</button>
        <a class="btn btn-secondary" href="Professor.php">Cancelar</a>
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