<?php
require_once __DIR__ . '/Conec.php';

function checkExistence($pdo, $table, $column, $value) {
    if (empty($value)) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$column} = :val");
    $stmt->execute([':val' => $value]);
    return (bool)$stmt->fetch();
}

$disciplinas_disponiveis = [
    'Educação Física', 
    'Filosofia', 
    'Língua Portuguesa', 
    'Matemática', 
    'Português', 
    'Redação', 
    'Sociologia',
    'Análise e Desenvolvimento de Projetos', 
    'Programação Web', 
    'Redes de Computadores'
];

try {
    $alunos = $pdo->query("SELECT Matricula, nomeAluno FROM Aluno ORDER BY nomeAluno")->fetchAll(PDO::FETCH_ASSOC);
    $turmas = $pdo->query("SELECT ID_turma, Semestre, Ano FROM Turma ORDER BY Ano DESC, Semestre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_avaliacao = ['Prova', 'Trabalho', 'Simulado'];
} catch (PDOException $e) {
    $alunos = $turmas = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_nota = (int)($_POST['id_nota'] ?? 0); 
    $matricula = trim($_POST['matricula'] ?? ''); 
    $id_turma = (int)($_POST['id_turma'] ?? 0); 
    $materia = trim($_POST['materia'] ?? ''); 
    $valor_nota = trim($_POST['valor_nota'] ?? ''); 
    $valor_nota = str_replace(',', '.', $valor_nota);
    $data_lancamento = trim($_POST['data_lancamento'] ?? ''); 
    $tipo_avaliacao = trim($_POST['tipo_avaliacao'] ?? ''); 

    $campos_base_validos = ($id_nota > 0 && $matricula !== '' && $id_turma > 0 && $valor_nota !== '' && $data_lancamento !== '' && $tipo_avaliacao !== '' && $materia !== '');

    if ($campos_base_validos) {
        if (!checkExistence($pdo, 'Aluno', 'Matricula', $matricula)) {
            header('Location: Nota.php?erro=fk_aluno'); 
            exit;
        }
        if (!checkExistence($pdo, 'Turma', 'ID_turma', $id_turma)) {
            header('Location: Nota.php?erro=fk_turma'); 
            exit;
        }
    }

    if ($acao === 'adicionar' && $campos_base_validos) {
        if (checkExistence($pdo, 'Nota', 'ID_nota', $id_nota)) {
            header('Location: Nota.php?erro=duplicidade_id'); 
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO Nota (ID_nota, Matricula, ID_turma, Valor_nota, Data_lancamento, Tipo_avaliacao, Materia) 
                                   VALUES (:idnota, :mat, :idturma, :valor, :data, :tipo, :materia)");
            $stmt->execute([
                ':idnota' => $id_nota, 
                ':mat' => $matricula, 
                ':idturma' => $id_turma, 
                ':valor' => $valor_nota, 
                ':data' => $data_lancamento, 
                ':tipo' => $tipo_avaliacao,
                ':materia' => $materia
            ]);
        } catch (PDOException $e) {
             header('Location: Nota.php?erro=db'); 
             exit;
        }
    }

    if ($acao === 'atualizar' && $campos_base_validos) {
        try {
            $up = $pdo->prepare("UPDATE Nota SET Matricula=:mat, ID_turma=:idturma, Valor_nota=:valor, Data_lancamento=:data, Tipo_avaliacao=:tipo, Materia=:materia 
                                 WHERE ID_nota=:idnota");
            $up->execute([
                ':mat' => $matricula, 
                ':idturma' => $id_turma, 
                ':valor' => $valor_nota, 
                ':data' => $data_lancamento, 
                ':tipo' => $tipo_avaliacao, 
                ':materia' => $materia,
                ':idnota' => $id_nota
            ]);
        } catch (PDOException $e) {
            header('Location: Nota.php?erro=db'); 
            exit;
        }
    }
    
    if ($acao === 'adicionar' || $acao === 'atualizar') {
        header('Location: Nota.php'); 
        exit;
    }
}

if (($_GET['acao'] ?? '') === 'excluir') {
    $id = (int)($_GET['ID_nota'] ?? 0);
    if ($id > 0) {
        $del = $pdo->prepare("DELETE FROM Nota WHERE ID_nota = :id");
        $del->execute([':id' => $id]);
    }
    header('Location: Nota.php');
    exit;
}

$editando = false;
$notaEdit = [
    'ID_nota' => 0,
    'Matricula' => '',
    'ID_turma' => '',
    'Valor_nota' => '',
    'Data_lancamento' => date('Y-m-d'),
    'Tipo_avaliacao' => '',
    'Materia' => ''
];

if (($_GET['acao'] ?? '') === 'editar') {
    $id = (int)($_GET['ID_nota'] ?? 0);
    if ($id > 0) {
        $s = $pdo->prepare("SELECT ID_nota, Matricula, ID_turma, Valor_nota, Data_lancamento, Tipo_avaliacao, Materia FROM Nota WHERE ID_nota = :id");
        $s->execute([':id' => $id]);
        if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $editando = true;
            $notaEdit = $row;
        }
    }
}

$sqlLista = "
    SELECT 
        n.ID_nota, 
        n.Valor_nota, 
        n.Data_lancamento, 
        n.Tipo_avaliacao,
        n.Materia,
        a.Matricula, 
        a.nomeAluno, 
        t.ID_turma
    FROM 
        Nota n
    INNER JOIN 
        Aluno a ON n.Matricula = a.Matricula
    INNER JOIN 
        Turma t ON n.ID_turma = t.ID_turma
    ORDER BY 
        n.Data_lancamento DESC";

$lista = $pdo->query($sqlLista)->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
if (isset($_GET['erro'])) {
    if ($_GET['erro']==='fk_aluno') {
        $msg = '<div class="alert alert-error">Erro: A Matrícula informada não existe.</div>';
    } elseif ($_GET['erro']==='fk_turma') {
        $msg = '<div class="alert alert-error">Erro: O ID da Turma informado não existe.</div>';
    } elseif ($_GET['erro']==='duplicidade_id') {
        $msg = '<div class="alert alert-error">Erro: O ID da Nota já está em uso.</div>';
    } elseif ($_GET['erro']==='db') {
        $msg = '<div class="alert alert-error">Erro no Banco de Dados.</div>';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>CRUD Simples de Notas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="container">
        <h1>Notas Lançadas</h1>
        <nav><a href="Nota.php" class="btn">Nova Nota</a></nav>
    </div>
</header>

<main class="container">
    <?= $msg ?>

    <h2>Lista de Notas</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID Nota</th>
                <th>Aluno (Matrícula)</th>
                <th>Turma (ID)</th>
                <th>Matéria</th>
                <th>Nota</th>
                <th>Data</th>
                <th>Avaliação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lista)): ?>
            <tr><td colspan="8">Nenhuma nota cadastrada.</td></tr>
        <?php else: ?>
            <?php foreach ($lista as $n): ?>
                <tr>
                    <td><?= htmlspecialchars($n['ID_nota']) ?></td>
                    <td><?= htmlspecialchars($n['nomeAluno']) ?> (<?= htmlspecialchars($n['Matricula']) ?>)</td>
                    <td><?= htmlspecialchars($n['ID_turma']) ?></td>
                    <td><?= htmlspecialchars($n['Materia']) ?></td>
                    <td><?= number_format((float)$n['Valor_nota'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($n['Data_lancamento']) ?></td>
                    <td><?= htmlspecialchars($n['Tipo_avaliacao']) ?></td>
                    <td class="acao">
                        <a class="btn btn-secondary" href="?acao=editar&ID_nota=<?= urlencode($n['ID_nota']) ?>">Editar</a>
                        <a class="btn btn-danger" href="?acao=excluir&ID_nota=<?= urlencode($n['ID_nota']) ?>" onclick="return confirm('Excluir esta nota?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <hr>
    
    <form class="form-card" method="post">
        <h2><?= $editando ? 'Editar Nota (ID: ' . htmlspecialchars($notaEdit['ID_nota']) . ')' : 'Adicionar Nova Nota' ?></h2>

        <?php if ($editando): ?>
            <input type="hidden" name="id_nota" value="<?= htmlspecialchars($notaEdit['ID_nota']) ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="id_nota">ID Nota</label>
                <input type="number" id="id_nota" name="id_nota" required value="<?= htmlspecialchars($notaEdit['ID_nota']) ?>" <?= $editando ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label for="matricula">Aluno</label>
                <select id="matricula" name="matricula" required>
                    <option value="">Selecione o Aluno</option>
                    <?php 
                    $matEdit = htmlspecialchars($notaEdit['Matricula']);
                    foreach ($alunos as $a): 
                        $selected = ($a['Matricula'] == $matEdit) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($a['Matricula']) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($a['nomeAluno']) ?> (Mat: <?= htmlspecialchars($a['Matricula']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_turma">Turma</label>
                <select id="id_turma" name="id_turma" required>
                    <option value="">Selecione a Turma</option>
                    <?php 
                    $turmaEditID = htmlspecialchars($notaEdit['ID_turma']);
                    foreach ($turmas as $t): 
                        $selected = ($t['ID_turma'] == $turmaEditID) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($t['ID_turma']) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($t['Semestre']) ?> / <?= htmlspecialchars($t['Ano']) ?> (ID: <?= htmlspecialchars($t['ID_turma']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="valor_nota">Nota</label>
                <input type="number" id="valor_nota" name="valor_nota" step="0.01" min="0" max="999.99" required value="<?= htmlspecialchars($notaEdit['Valor_nota']) ?>">
            </div>

            <div class="form-group">
                <label for="data_lancamento">Data</label>
                <input type="date" id="data_lancamento" name="data_lancamento" required value="<?= htmlspecialchars($notaEdit['Data_lancamento']) ?>">
            </div>
            
            <div class="form-group">
                <label for="materia">Matéria</label>
                <select id="materia" name="materia" required>
                    <option value="">Selecione a Matéria</option>
                    <?php $materiaAtual = htmlspecialchars($notaEdit['Materia']); ?>
                    <?php foreach ($disciplinas_disponiveis as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>" <?= ($materiaAtual == $d ? 'selected' : '') ?>>
                            <?= htmlspecialchars($d) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tipo_avaliacao">Tipo</label>
                <select id="tipo_avaliacao" name="tipo_avaliacao" required>
                    <option value="">Selecione o Tipo</option>
                    <?php $tipoAtual = htmlspecialchars($notaEdit['Tipo_avaliacao']); ?>
                    <?php foreach ($tipos_avaliacao as $tipo): ?>
                        <option value="<?= htmlspecialchars($tipo) ?>" <?= ($tipoAtual == $tipo ? 'selected' : '') ?>>
                            <?= htmlspecialchars($tipo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <?php if ($editando): ?>
                <input type="hidden" name="acao" value="atualizar">
                <button class="btn" type="submit">Salvar Alterações</button>
                <a class="btn btn-secondary" href="Nota.php">Cancelar</a>
            <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <button class="btn" type="submit">Adicionar Nota</button>
            <?php endif; ?>
        </div>
    </form>
</main>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> — Sistema Escola (Notas)</small>
    </div>
</footer>

</body>
</html>