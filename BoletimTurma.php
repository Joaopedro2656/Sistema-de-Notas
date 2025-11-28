<?php
require_once __DIR__ . '/Conec.php'; 

$idTurmaSelecionada = null;
$resultadosBoletim = [];
$turmaInfo = null;

try {
    $turmas = $pdo->query("SELECT ID_turma, Semestre, Ano FROM Turma ORDER BY Ano DESC, Semestre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $turmas = [];
    $msg = '<div class="alert alert-error">Erro ao buscar turmas: ' . $e->getMessage() . '</div>';
}

if (isset($_REQUEST['idTurma'])) {
    $idTurmaSelecionada = filter_var($_REQUEST['idTurma'], FILTER_VALIDATE_INT);
}

if ($idTurmaSelecionada !== false && $idTurmaSelecionada > 0) {
    $stmtTurma = $pdo->prepare("SELECT Semestre, Ano FROM Turma WHERE ID_turma = :id");
    $stmtTurma->execute([':id' => $idTurmaSelecionada]);
    $turmaInfo = $stmtTurma->fetch(PDO::FETCH_ASSOC);

    $sqlBoletim = "
        SELECT 
            A.Matricula, 
            A.nomeAluno, 
            N.Materia,
            COUNT(N.ID_nota) AS TotalNotas,
            AVG(N.Valor_nota) AS MediaFinal
        FROM 
            Aluno A
        INNER JOIN 
            Nota N ON A.Matricula = N.Matricula
        WHERE 
            N.ID_turma = :idTurma
        GROUP BY 
            A.Matricula, A.nomeAluno, N.Materia
        ORDER BY 
            A.nomeAluno ASC, N.Materia ASC";
    
    $stmtBoletim = $pdo->prepare($sqlBoletim);
    $stmtBoletim->execute([':idTurma' => $idTurmaSelecionada]);
    $resultadosBoletimRaw = $stmtBoletim->fetchAll(PDO::FETCH_ASSOC);

    $resultadosBoletim = [];
    foreach ($resultadosBoletimRaw as $r) {
        $matricula = $r['Matricula'];
        if (!isset($resultadosBoletim[$matricula])) {
            $resultadosBoletim[$matricula] = [
                'Matricula' => $r['Matricula'],
                'nomeAluno' => $r['nomeAluno'],
                'medias' => []
            ];
        }
        $resultadosBoletim[$matricula]['medias'][$r['Materia']] = [
            'MediaFinal' => $r['MediaFinal'],
            'TotalNotas' => $r['TotalNotas']
        ];
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Boletim e Média por Turma</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        .aprovado { color: green; font-weight: bold; }
        .reprovado { color: red; font-weight: bold; }
        .detalhe { font-size: 0.9em; margin-top: 10px; border-left: 5px solid #007bff; padding-left: 10px; }
        .aluno-card { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .aluno-card h3 { margin-top: 0; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Boletim Detalhado da Turma</h1>
        <nav><a href="BoletimTurma.php" class="btn">Nova Consulta</a></nav> 
    </div>
</header>

<main class="container">
    <?= $msg ?? '' ?>

    <form class="form-card" method="get" action="BoletimTurma.php">
        <h2>Selecione a Turma</h2>
        <div class="form-group">
            <label for="idTurma">Turma:</label>
            <select id="idTurma" name="idTurma" required onchange="this.form.submit()">
                <option value="">-- Selecione uma Turma --</option>
                <?php foreach ($turmas as $t): ?>
                    <option 
                        value="<?= htmlspecialchars($t['ID_turma']) ?>"
                        <?= ($idTurmaSelecionada == $t['ID_turma']) ? 'selected' : '' ?>
                    >
                        Turma ID: <?= htmlspecialchars($t['ID_turma']) ?> (<?= htmlspecialchars($t['Semestre']) ?> / <?= htmlspecialchars($t['Ano']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <hr>
    
    <?php if ($turmaInfo && $idTurmaSelecionada): ?>
        <h2>Boletim da Turma: <?= htmlspecialchars($turmaInfo['Semestre']) ?> / <?= htmlspecialchars($turmaInfo['Ano']) ?> (ID: <?= htmlspecialchars($idTurmaSelecionada) ?>)</h2>
        
        <?php if (empty($resultadosBoletim)): ?>
            <p>Nenhum aluno com notas lançadas nesta turma.</p>
        <?php else: ?>
            <?php 
            $mediaMinima = 7.0;
            foreach ($resultadosBoletim as $aluno): 
            ?>
                <div class="aluno-card">
                    <h3><?= htmlspecialchars($aluno['nomeAluno']) ?> <small>(Matrícula: <?= htmlspecialchars($aluno['Matricula']) ?>)</small></h3>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Matéria</th>
                                <th>Média Final</th>
                                <th>Total de Notas</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($aluno['medias'] as $materia => $dados): 
                                $media = (float)$dados['MediaFinal'];
                                $situacao = ($media >= $mediaMinima) ? 'Aprovado' : 'Reprovado';
                                $classe = ($media >= $mediaMinima) ? 'aprovado' : 'reprovado';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($materia) ?></td>
                                    <td><?= number_format($media, 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($dados['TotalNotas']) ?></td>
                                    <td class="<?= $classe ?>"><?= $situacao ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <p><small>Critério: Média Mínima de Aprovação: <?= number_format($mediaMinima, 1, ',', '.') ?></small></p>

        <?php endif; ?>

    <?php elseif ($idTurmaSelecionada): ?>
        <p>A Turma ID <?= htmlspecialchars($idTurmaSelecionada) ?> foi selecionada, mas ocorreu um erro ou ela não existe.</p>
    <?php else: ?>
        <p>Selecione uma turma para visualizar o boletim.</p>
    <?php endif; ?>
</main>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> — Sistema Escola (Boletim)</small>
    </div>
</footer>
</body>
</html>