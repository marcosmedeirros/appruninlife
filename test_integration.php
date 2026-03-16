#!/php
<?php
/**
 * Script de Teste - Verificar Integração do App de Apostas
 *
 * Execute este arquivo para validar se a integração foi bem-sucedida
 */

require_once __DIR__ . '/config.php';

echo "\n========================================\n";
echo "TESTE DE INTEGRAÇÃO - APP DE APOSTAS\n";
echo "========================================\n\n";

// 1. Verificar se as tabelas existem
echo "1. Verificando tabelas no banco de dados...\n";
try {
    $tables_to_check = ['bets', 'bet_selections'];
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . getenv('DB_NAME') ?: 'u289267434_runlife' . "' AND TABLE_NAME IN ('bets', 'bet_selections')");
    $count = $result->fetch()['count'];

    if ($count === 2) {
        echo "   ✅ Tabelas 'bets' e 'bet_selections' existem\n";
    } else {
        echo "   ⚠️  Apenas $count de 2 tabelas encontradas\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 2. Verificar estrutura da tabela bets
echo "\n2. Verificando estrutura da tabela 'bets'...\n";
try {
    $columns = $pdo->query("DESCRIBE bets")->fetchAll();
    $expected_columns = ['id', 'bet_date', 'odds', 'stake', 'result', 'profit', 'created_at'];

    foreach ($expected_columns as $col) {
        $found = false;
        foreach ($columns as $c) {
            if ($c['Field'] === $col) {
                $found = true;
                break;
            }
        }
        echo $found ? "   ✅ Coluna '$col' existe\n" : "   ❌ Coluna '$col' não encontrada\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 3. Verificar estrutura da tabela bet_selections
echo "\n3. Verificando estrutura da tabela 'bet_selections'...\n";
try {
    $columns = $pdo->query("DESCRIBE bet_selections")->fetchAll();
    $expected_columns = ['id', 'bet_id', 'comp', 'descr', 'sort_order'];

    foreach ($expected_columns as $col) {
        $found = false;
        foreach ($columns as $c) {
            if ($c['Field'] === $col) {
                $found = true;
                break;
            }
        }
        echo $found ? "   ✅ Coluna '$col' existe\n" : "   ❌ Coluna '$col' não encontrada\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 4. Testar inserção de uma aposta de teste
echo "\n4. Testando inserção de aposta...\n";
try {
    $pdo->beginTransaction();

    // Inserir aposta
    $stmt = $pdo->prepare("INSERT INTO bets (bet_date, odds, stake, result, profit) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        date('Y-m-d'),
        1.90,
        50.00,
        'Green',
        45.00
    ]);

    $bet_id = $pdo->lastInsertId();

    // Inserir seleção
    $stmt = $pdo->prepare("INSERT INTO bet_selections (bet_id, comp, descr, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $bet_id,
        'Premier League',
        'Arsenal para vencer',
        1
    ]);

    $pdo->commit();
    echo "   ✅ Aposta inserida com sucesso (ID: $bet_id)\n";

    // Limpar dados de teste
    $pdo->prepare("DELETE FROM bet_selections WHERE bet_id = ?")->execute([$bet_id]);
    $pdo->prepare("DELETE FROM bets WHERE id = ?")->execute([$bet_id]);
    echo "   ✅ Dados de teste removidos\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 5. Verificar arquivo app.php
echo "\n5. Verificando arquivo app.php...\n";
if (file_exists(__DIR__ . '/app.php')) {
    echo "   ✅ app.php existe\n";
    $content = file_get_contents(__DIR__ . '/app.php');
    if (strpos($content, '/app_api.php') !== false) {
        echo "   ✅ app.php referencia /app_api.php\n";
    } else {
        echo "   ⚠️  app.php não referencia /app_api.php\n";
    }
} else {
    echo "   ❌ app.php não encontrado\n";
}

// 6. Verificar arquivo app_api.php
echo "\n6. Verificando arquivo app_api.php...\n";
if (file_exists(__DIR__ . '/app_api.php')) {
    echo "   ✅ app_api.php existe\n";
} else {
    echo "   ❌ app_api.php não encontrado\n";
}

// 7. Verificar roteamento no index.php
echo "\n7. Verificando roteamento em index.php...\n";
$content = file_get_contents(__DIR__ . '/index.php');
if (strpos($content, '/app') !== false && strpos($content, 'include __DIR__ . \'/app.php\'') !== false) {
    echo "   ✅ Roteamento para /app configurado em index.php\n";
} else {
    echo "   ⚠️  Roteamento pode não estar correto\n";
}

echo "\n========================================\n";
echo "TESTE CONCLUÍDO\n";
echo "========================================\n\n";

// Instruções finais
echo "Para acessar a aplicação de apostas:\n";
echo "  - Desenvolução: http://localhost/lifeos/app\n";
echo "  - Produção: https://seu-dominio.com/app\n\n";
?>

