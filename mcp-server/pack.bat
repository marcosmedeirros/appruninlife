@echo off
echo ================================================
echo  Empacotando Vida em Controle como extensao MCP
echo ================================================

echo.
echo [1/2] Instalando mcpb...
npm install -g @anthropic-ai/mcpb
if %errorlevel% neq 0 (
    echo ERRO ao instalar mcpb. Verifique se o Node.js esta instalado.
    pause
    exit /b 1
)

echo.
echo [2/2] Empacotando...
mcpb pack
if %errorlevel% neq 0 (
    echo ERRO ao empacotar. Verifique o manifest.json.
    pause
    exit /b 1
)

echo.
echo ================================================
echo  Pronto! Arquivo .mcpb gerado nesta pasta.
echo
echo  Proximo passo:
echo  1. Abra o Claude desktop
echo  2. Va em Settings ^> Extensions ^> Advanced settings
echo  3. Clique em "Install Extension..." e selecione o .mcpb
echo ================================================
pause
