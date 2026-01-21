@echo off
title Servidor de Chat Seguro - LookEmploy
color 0A

echo ==========================================
echo   INICIANDO SERVIDOR DE CHAT SEGURO
echo ==========================================
echo.

cd /d "%~dp0api_chat"

echo Verificando PHP...
php -v >nul 2>&1
if errorlevel 1 (
    echo [ERRO] PHP nao encontrado no PATH
    echo.
    echo Por favor, adicione o PHP ao PATH do sistema ou execute:
    echo C:\xampp\php\php.exe servidor_chat_seguro.php
    pause
    exit /b 1
)

echo [OK] PHP encontrado
echo.
echo Iniciando servidor na porta 8080...
echo.
echo IMPORTANTE: Nao feche esta janela!
echo O servidor precisa estar rodando para o chat funcionar.
echo.
echo ==========================================
echo.

"C:\xampp\php\php.exe" servidor_chat_seguro.php

pause
