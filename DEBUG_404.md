# 🔧 DEBUG - Erro 404 em /app_api.php

## Problema
Ao acessar `/app`, o app tenta carregar dados da API `/app_api.php?action=list` e retorna 404.

## Causas Possíveis

### 1. Arquivo não existe no servidor
```bash
# Verifique se o arquivo existe
ls -la app_api.php
# Deve listar o arquivo
```

### 2. `.htaccess` não está permitindo
```bash
# Teste acesso direto
curl https://marcosmedeiros.page/app_api.php?action=list
# Deve retornar JSON ou erro, não 404
```

### 3. Permissões incorretas
```bash
# Verifique permissões
chmod 644 app_api.php
chmod 755 .
```

## Soluções

### Solução 1: Teste Direto
Abra no navegador:
```
https://marcosmedeiros.page/test_api.php
```

Se retornar JSON com "status: ok", o PHP está funcionando.

### Solução 2: Verifique .htaccess
O `.htaccess` deve ter:
```
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]
```

Isso permite acesso a arquivos que existem.

### Solução 3: Teste sem .htaccess
Renomeie `.htaccess` temporariamente:
```bash
mv .htaccess .htaccess.bak
# Teste acesso a /app_api.php
# Se funcionar, problema está no .htaccess
```

### Solução 4: Verifique o servidor
Alguns servidores requerem:
```
RewriteEngine On
RewriteBase /
```

No topo do `.htaccess`.

## Debug no Console

1. Abra DevTools (F12)
2. Vá na aba Console
3. Veja os logs:
   - `BASE_PATH:` deve ser `/` ou `/lifeos/`
   - `API_URL:` deve ser `/app_api.php`
   - Requisição deve sair para a URL correta

## Arquivos para Verificar

```
✓ app_api.php           - Deve existir e ser executável
✓ .htaccess             - Deve permitir acesso a app_api.php
✓ config.php            - Deve existir (incluído por app_api.php)
✓ index.php             - Deve existir
✓ app.php               - Deve existir
```

## Comando para Teste Completo

```bash
# 1. Verifique arquivo
ls -la app_api.php

# 2. Teste direto via PHP
php app_api.php

# 3. Teste via curl (requer app_api.php no root)
curl "https://marcosmedeiros.page/app_api.php?action=list"

# 4. Teste permissões
chmod 644 app_api.php config.php index.php app.php
chmod 755 .
```

## Se Nada Funcionar

Contate o suporte do servidor e forneça:
1. Erro exato do console (F12)
2. Saída de: `php app_api.php`
3. Versão do PHP
4. Se Apache mod_rewrite está ativado

