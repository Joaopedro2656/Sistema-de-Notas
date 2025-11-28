<?php
require_once __DIR__ . '/Conec.php'; 
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit();
}

$matriculaSelecionada = null;
$alunoInfo = null;
$notasAluno = [];
$msg = ''; 
$mediaMinima = 7.0; 

$nivelAcesso = $_SESSION['nivel_acesso']; 
$isProfessor = ($nivelAcesso === 'professor');
$isAluno = ($nivelAcesso === 'aluno');

if ($isAluno) {
    $matriculaSelecionada = $_SESSION['id_usuario'];
    $alunos = [];
} else {
    try {
        $alunos = $pdo->query("SELECT Matricula, nomeAluno, Curso FROM Aluno ORDER BY nomeAluno ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alunos = [];
        $msg = '<div class="alert alert-error">Erro ao buscar alunos: ' . $e->getMessage() . '</div>';
    }

    if (isset($_REQUEST['matricula'])) {
        $matriculaSelecionada = trim($_REQUEST['matricula']);
    }
}

if ($matriculaSelecionada) {
    $stmtAluno = $pdo->prepare("SELECT nomeAluno, Curso FROM Aluno WHERE Matricula = :mat");
    $stmtAluno->execute([':mat' => $matriculaSelecionada]);
    $alunoInfo = $stmtAluno->fetch(PDO::FETCH_ASSOC);

    if ($alunoInfo) {
        $sqlNotas = "
            SELECT 
                N.ID_nota, 
                N.Materia, 
                N.Tipo_avaliacao,
                N.Valor_nota,
                N.Data_lancamento,
                T.ID_turma,
                T.Ano,
                T.Semestre
            FROM 
                Nota N
            INNER JOIN 
                Turma T ON N.ID_turma = T.ID_turma
            WHERE 
                N.Matricula = :mat
            ORDER BY 
                N.Materia ASC, N.Data_lancamento DESC";
        
        $stmtNotas = $pdo->prepare($sqlNotas);
        $stmtNotas->execute([':mat' => $matriculaSelecionada]);
        $notasRaw = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notasRaw as $nota) {
            $materia = $nota['Materia'];
            if (!isset($notasAluno[$materia])) {
                $notasAluno[$materia] = [
                    'notas' => [],
                    'total_notas' => 0,
                    'soma_notas' => 0.0
                ];
            }
            $valor_nota = (float)$nota['Valor_nota'];
            $notasAluno[$materia]['notas'][] = $nota;
            $notasAluno[$materia]['total_notas']++;
            $notasAluno[$materia]['soma_notas'] += $valor_nota;
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Novo Sistema de Boletim</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
        }
        .container {
            max-width: 960px;
        }
        .aprovado { color: #10b981; font-weight: 700; }
        .reprovado { color: #ef4444; font-weight: 700; }
    </style>
</head>
<body class="p-4 md:p-8">

<header class="bg-white shadow-md rounded-lg mb-8">
    <div class="container mx-auto p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Boletim Escolar Digital</h1>
        <nav class="space-x-4">
            <?php if ($isProfessor): ?>
            <a href="boletimaluno_novo.php" class="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">Nova Consulta</a>
            <?php endif; ?>
            <a href="logout.php" class="px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Sair</a>
        </nav>
    </div>
</header>

<main class="container mx-auto">
    <div class="mb-6">
        <?= $msg ?>
    </div>

    <?php if ($isProfessor): ?>
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h2 class="text-xl font-semibold text-gray-700 border-b pb-3 mb-4">Consulta de Aluno</h2>
            <form method="get" action="boletimaluno_novo.php" class="flex flex-col sm:flex-row items-center gap-4">
                <div class="flex-grow w-full">
                    <label for="matricula" class="block text-sm font-medium text-gray-600 mb-1">Selecione o Aluno:</label>
                    <select id="matricula" name="matricula" required onchange="this.form.submit()" 
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        <option value="">-- Selecione um Aluno --</option>
                        <?php foreach ($alunos as $a): ?>
                            <option 
                                value="<?= htmlspecialchars($a['Matricula']) ?>"
                                <?= ($matriculaSelecionada == $a['Matricula']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($a['nomeAluno']) ?> (Matrícula: <?= htmlspecialchars($a['Matricula']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if ($alunoInfo): ?>
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold text-blue-700 mb-3"><?= htmlspecialchars($alunoInfo['nomeAluno']) ?></h2>
            <div class="flex flex-wrap gap-x-6 text-gray-600 mb-6 border-b pb-4">
                <p><strong>Matrícula:</strong> <?= htmlspecialchars($matriculaSelecionada) ?></p>
                <p><strong>Curso:</strong> <?= htmlspecialchars($alunoInfo['Curso']) ?></p>
            </div>
            
            <?php if (empty($notasAluno)): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg" role="alert">
                    <p class="font-bold">Atenção:</p>
                    <p>Nenhuma nota foi lançada para este aluno ainda.</p>
                </div>
            <?php else: ?>
                <?php 
                foreach ($notasAluno as $materia => $dados): 
                    $media = $dados['total_notas'] > 0 ? $dados['soma_notas'] / $dados['total_notas'] : 0;
                    $situacao = ($media >= $mediaMinima) ? 'Aprovado' : 'Reprovado';
                    $classe = ($media >= $mediaMinima) ? 'aprovado' : 'reprovado';
                ?>
                    <div class="materia-section border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
                        <h3 class="text-xl font-semibold mb-3 text-blue-800 border-b border-blue-100 pb-2">Matéria: <?= htmlspecialchars($materia) ?></h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Nota</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dados['notas'] as $nota): ?>
                                        <tr>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= number_format((float)$nota['Valor_nota'], 2, ',', '.') ?></td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($nota['Tipo_avaliacao']) ?></td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($nota['Data_lancamento']) ?></td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($nota['ID_turma']) ?> (<?= htmlspecialchars($nota['Semestre']) ?>/<?= htmlspecialchars($nota['Ano']) ?>)</td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($nota['ID_nota']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="media-final mt-4 p-3 bg-blue-50 rounded-lg flex justify-between items-center">
                            <p class="font-bold text-gray-700">Média Final na Matéria:</p>
                            <p class="text-lg">
                                <span class="<?= $classe ?>"><?= number_format($media, 2, ',', '.') ?></span>
                                <span class="text-sm text-gray-500">(<?= htmlspecialchars($dados['total_notas']) ?> notas)</span>
                            </p>
                            <p class="font-bold text-gray-700">Situação:</p>
                            <p class="text-lg">
                                <span class="<?= $classe ?>"><?= $situacao ?></span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <p class="mt-6 text-sm text-gray-500">
                    <strong class="font-semibold">Nota:</strong> Média Mínima de Aprovação: <?= number_format($mediaMinima, 1, ',', '.') ?>
                </p>

            <?php endif; ?>

        </div>
    <?php elseif ($matriculaSelecionada): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
            <p class="font-bold">Erro na Consulta:</p>
            <p>O aluno com Matrícula <?= htmlspecialchars($matriculaSelecionada) ?> não foi encontrado.</p>
        </div>
    <?php else: ?>
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg" role="alert">
            <p class="font-bold">Aguardando Seleção:</p>
            <p>Selecione um aluno acima para visualizar o boletim.</p>
        </div>
    <?php endif; ?>
</main>

<footer class="mt-8 text-center text-gray-500">
    <div class="container mx-auto">
        <small>&copy; <?= date('Y') ?> — Novo Sistema de Boletim Escolar</small>
    </div>
</footer>
</body>
</html>