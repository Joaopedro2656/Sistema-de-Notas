<?php
// Assumindo que este arquivo é 'Turma.php'
require_once __DIR__ . '/Conec.php'; // Inclui a conexão com o banco de dados

// --- Leitura da Lista de Professores para o Select ---
try {
    $professores = $pdo->query("SELECT ID_Professor, nomeProfessor FROM Professor ORDER BY nomeProfessor")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Caso a tabela Professor não exista ou haja erro de DB.
    $professores = []; 
    // Opcional: Adicionar mensagem de erro, se necessário.
}
// --- Processamento de Formulário (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';
  
  // Capturando e sanitizando os campos
  // ID_turma e ID_Professor devem ser INT
  $idTurma = filter_var(trim($_POST['idTurma'] ?? ''), FILTER_VALIDATE_INT);
  $idProfessor = filter_var(trim($_POST['idProfessor'] ?? ''), FILTER_VALIDATE_INT);
  
  // Ano deve ser INT (YEAR)
  $ano = filter_var(trim($_POST['ano'] ?? ''), FILTER_VALIDATE_INT);

  // CORREÇÃO: Semestre é VARCHAR(10) no BD, deve ser tratado como string
  $semestre = trim($_POST['semestre'] ?? ''); 

  $original = filter_var(trim($_POST['original'] ?? ''), FILTER_VALIDATE_INT); // Usado para Atualizar

  // Verifica se ID_Turma, ID_Professor e Ano são válidos (números inteiros) e Semestre não está vazio
  $camposValidos = ($idTurma !== false && $idProfessor !== false && $ano !== false && $semestre !== '');

  // Ação Adicionar
  if ($acao === 'adicionar' && $camposValidos) {
    // CORREÇÃO SQL: Colunas ID_turma, ID_Professor, Ano, Semestre
    $stmt = $pdo->prepare("INSERT INTO Turma (ID_turma, ID_Professor, Ano, Semestre) VALUES (:it, :ip, :a, :s)");
    
    try {
        $stmt->execute([':it' => $idTurma, ':ip' => $idProfessor, ':a' => $ano, ':s' => $semestre]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
            header('Location: Turma.php?erro=duplicidade'); 
            exit;
        }
        // Opcional: Tratar erro de FK (ID_Professor inexistente)
    }
  }

  // Ação Atualizar
  if ($acao === 'atualizar' && $original !== false && $camposValidos) {
    if ($original !== $idTurma) {
      // Verifica se o novo ID_turma já existe (se o ID foi alterado)
      $chk = $pdo->prepare("SELECT 1 FROM Turma WHERE ID_turma = :it");
      $chk->execute([':it' => $idTurma]);
      if ($chk->fetch()) {
        header('Location: Turma.php?erro=duplicidade');
        exit;
      }

      // CORREÇÃO SQL: Colunas ID_turma, ID_Professor, Ano, Semestre
      $up = $pdo->prepare("UPDATE Turma SET ID_turma=:it, ID_Professor=:ip, Ano=:a, Semestre=:s WHERE ID_turma=:o");
      $up->execute([':it'=>$idTurma, ':ip'=>$idProfessor, ':a'=>$ano, ':s'=>$semestre, ':o'=>$original]);
    } else {
      // CORREÇÃO SQL: Colunas ID_Professor, Ano, Semestre
      $up = $pdo->prepare("UPDATE Turma SET ID_Professor=:ip, Ano=:a, Semestre=:s WHERE ID_turma=:it");
      $up->execute([':ip'=>$idProfessor, ':a'=>$ano, ':s'=>$semestre, ':it'=>$idTurma]);
    }
  }
}

// --- Ação Excluir (GET) ---
if (($_GET['acao'] ?? '') === 'excluir') {
  $id = filter_var($_GET['idTurma'] ?? '', FILTER_VALIDATE_INT);
  if ($id !== false) {
    // Exclui a Turma
    $del = $pdo->prepare("DELETE FROM Turma WHERE ID_turma = :id");
    $del->execute([':id' => $id]);
  }
}

// --- Lógica para Edição ---
$editando = false;

// CORREÇÃO PHP: Chaves do array ajustadas
$turmaEdit = ['ID_turma'=>'', 'ID_Professor'=>'', 'Ano'=>'', 'Semestre'=>'']; 
if (($_GET['acao'] ?? '') === 'editar') {
  $id = filter_var($_GET['idTurma'] ?? '', FILTER_VALIDATE_INT);
  if ($id !== false) {
    // CORREÇÃO SQL: Colunas ID_turma, ID_Professor, Ano, Semestre
    $s = $pdo->prepare("SELECT ID_turma, ID_Professor, Ano, Semestre FROM Turma WHERE ID_turma = :id");
    $s->execute([':id' => $id]);
    if ($row = $s->fetch(PDO::FETCH_ASSOC)) { 
      $editando = true; 
      // CORREÇÃO: Atribuição usando as chaves corretas
      $turmaEdit = $row; 
    }
  }
}

// --- Leitura da Lista de Turmas ---
// CORREÇÃO SQL: Colunas ID_turma, ID_Professor, Ano, Semestre
// VERSÃO COM JOIN (Melhor visualização na tabela)
$sqlLista = "
    SELECT 
        t.ID_turma, 
        t.Ano, 
        t.Semestre, 
        p.nomeProfessor, -- Seleciona o nome do professor
        t.ID_Professor    -- Mantém o ID por segurança
    FROM 
        Turma t
    INNER JOIN 
        Professor p ON t.ID_Professor = p.ID_Professor
    ORDER BY 
        t.Ano DESC, t.Semestre DESC";

$lista = $pdo->query($sqlLista)->fetchAll(PDO::FETCH_ASSOC);
// --- Mensagem de Erro ---
$msg = '';
if (isset($_GET['erro']) && $_GET['erro']==='duplicidade') {
  $msg = '<div class="alert alert-error">ID da Turma já existente.</div>';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>CRUD Simples de Turmas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css" >
</head>
<body>

<header>
  <div class="container">
    <h1>Turmas</h1>
    <nav><a href="Turma.php" class="btn">Atualizar Lista</a></nav>
  </div>
</header>

<main class="container">
  <?= $msg ?>

  <h2>Lista de Turmas</h2>
  <table class="table">
    <thead>
      <tr>
        <th>ID Turma</th>
        <th>ID Professor</th>
        <th>Ano</th>
        <th>Semestre</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($lista)): ?>
      <tr><td colspan="5">Nenhuma turma cadastrada.</td></tr> 
    <?php else: ?>
      <?php foreach ($lista as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['ID_turma']) ?></td>
          <td><?= htmlspecialchars($t['ID_Professor']) ?></td>
          <td><?= htmlspecialchars($t['Ano']) ?></td>
          <td><?= htmlspecialchars($t['Semestre']) ?></td>
          <td class="acao">
            <a class="btn btn-secondary" href="?acao=editar&idTurma=<?= urlencode($t['ID_turma']) ?>">Editar</a>
            <a class="btn btn-danger" href="?acao=excluir&idTurma=<?= urlencode($t['ID_turma']) ?>"
                onclick="return confirm('Excluir esta turma?');">Excluir</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  
  <hr>
  
  <form class="form-card" method="post">
    <h2><?= $editando ? 'Editar Turma' : 'Adicionar Turma' ?></h2>

    <?php if ($editando): ?>
      <input type="hidden" name="original" value="<?= htmlspecialchars($turmaEdit['ID_turma']) ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="idTurma">ID Turma (int)</label>
        <input type="number" id="idTurma" name="idTurma" required
                value="<?= htmlspecialchars($turmaEdit['ID_turma']) ?>">
      </div>
      <div class="form-group">
    <label for="idProfessor">Professor Responsável</label>
    <select id="idProfessor" name="idProfessor" required>
        <option value="">Selecione um Professor</option>
        <?php 
        $professorSelecionado = htmlspecialchars($turmaEdit['ID_Professor']);
        foreach ($professores as $p): 
            $selected = ($p['ID_Professor'] == $professorSelecionado) ? 'selected' : '';
        ?>
            <option value="<?= htmlspecialchars($p['ID_Professor']) ?>" <?= $selected ?>>
                <?= htmlspecialchars($p['nomeProfessor']) ?> (ID: <?= htmlspecialchars($p['ID_Professor']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
      <div class="form-group">
        <label for="ano">Ano (ex: 2025)</label>
        <input type="number" id="ano" name="ano" required
                value="<?= htmlspecialchars($turmaEdit['Ano']) ?>">
      </div>
      <div class="form-group">
        <label for="semestre">Semestre (ex: 1º Semestre, máx. 10)</label>
        <input type="text" id="semestre" name="semestre" maxlength="10" required
                value="<?= htmlspecialchars($turmaEdit['Semestre']) ?>">
      </div>
    </div>
    
    <div class="form-actions">
      <?php if ($editando): ?>
        <input type="hidden" name="acao" value="atualizar">
        <button class="btn" type="submit">Salvar Alterações</button>
        <a class="btn btn-secondary" href="Turma.php">Cancelar</a>
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