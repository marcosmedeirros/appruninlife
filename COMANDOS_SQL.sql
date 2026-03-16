-- COMANDOS SQL PARA VERIFICAR O BANCO DE DADOS
-- Execute estes comandos no seu painel MySQL/phpMyAdmin

-- 1. VERIFICAR SE AS TABELAS EXISTEM
SHOW TABLES LIKE 'bets';
SHOW TABLES LIKE 'bet_selections';

-- 2. VERIFICAR ESTRUTURA DAS TABELAS
DESCRIBE bets;
DESCRIBE bet_selections;

-- 3. CONTAR REGISTROS
SELECT COUNT(*) as total_apostas FROM bets;
SELECT COUNT(*) as total_selecoes FROM bet_selections;

-- 4. LISTAR TODAS AS APOSTAS
SELECT * FROM bets ORDER BY created_at DESC;

-- 5. LISTAR APOSTAS COM SUAS SELEÇÕES
SELECT
    b.id,
    b.bet_date,
    b.odds,
    b.stake,
    b.result,
    b.profit,
    GROUP_CONCAT(CONCAT(bs.comp, ' - ', bs.descr) SEPARATOR ' | ') as selecoes
FROM bets b
LEFT JOIN bet_selections bs ON b.id = bs.bet_id
GROUP BY b.id
ORDER BY b.created_at DESC;

-- 6. LIMPAR DADOS DE TESTE (SE NECESSÁRIO)
-- DELETE FROM bet_selections;
-- DELETE FROM bets;

-- 7. INSERIR APOSTA DE TESTE
INSERT INTO bets (bet_date, odds, stake, result, profit)
VALUES (CURDATE(), 2.50, 100.00, 'Green', 150.00);

-- Pegar o ID da última aposta inserida
SET @bet_id = LAST_INSERT_ID();

-- 8. INSERIR SELEÇÃO PARA A APOSTA
INSERT INTO bet_selections (bet_id, comp, descr, sort_order)
VALUES (@bet_id, 'Premier League', 'Manchester United para vencer', 1);

-- 9. VERIFICAR SE FOI INSERIDO
SELECT * FROM bets WHERE id = @bet_id;
SELECT * FROM bet_selections WHERE bet_id = @bet_id;

-- 10. ESTATÍSTICAS GERAIS
SELECT
    COUNT(*) as total_apostas,
    SUM(stake) as total_investido,
    SUM(profit) as lucro_total,
    AVG(odds) as odds_media,
    COUNTIF(result = 'Green') as greens,
    COUNTIF(result = 'Red') as reds
FROM bets;

