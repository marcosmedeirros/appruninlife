# Migrations automáticas

Para rodar as migrations automaticamente ao subir o projeto, adicione o seguinte comando ao seu deploy ou script de inicialização:

```
php artisan migrate --force
```

Isso garante que todas as migrations (v1, v2, ...) rodem em ordem.

**Importante:**
- Remova as migrations antigas (2026_01_27_000000_create_habits_table.php, 2014_10_12_000000_create_users_table.php) para evitar duplicidade.
- As novas migrations já seguem o padrão v1, v2, v3...
- O Laravel executa as migrations em ordem alfabética, então o padrão v1, v2, v3 garante a ordem correta.

No deploy Hostinger, adicione o comando acima no painel ou script pós-upload.
