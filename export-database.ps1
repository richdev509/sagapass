# Script PowerShell pour exporter la base de données MySQL locale

# Configuration
$mysqlPath = "C:\xampp\mysql\bin\mysqldump.exe"  # Ajustez selon votre installation
$database = "saga_id"
$username = "root"
$password = "3777"
$outputFile = "saga_id_export.sql"

# Vérifier si mysqldump existe
if (-Not (Test-Path $mysqlPath)) {
    Write-Host "❌ mysqldump non trouvé à: $mysqlPath" -ForegroundColor Red
    Write-Host "📝 Chemins possibles:" -ForegroundColor Yellow
    Write-Host "   - C:\xampp\mysql\bin\mysqldump.exe (XAMPP)"
    Write-Host "   - C:\wamp64\bin\mysql\mysql8.x.x\bin\mysqldump.exe (WAMP)"
    Write-Host "   - C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe (MySQL)"
    exit
}

Write-Host "🔄 Export de la base de données '$database'..." -ForegroundColor Cyan

# Exécuter mysqldump
& $mysqlPath --user=$username --password=$password --databases $database --result-file=$outputFile

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Export réussi!" -ForegroundColor Green
    Write-Host "📁 Fichier créé: $outputFile" -ForegroundColor Green
    Write-Host "📊 Taille: $((Get-Item $outputFile).Length / 1KB) KB" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "🚀 Prochaines étapes:" -ForegroundColor Yellow
    Write-Host "   1. Transférez ce fichier vers votre VPS via WinSCP/FileZilla"
    Write-Host "   2. Ou importez-le via phpMyAdmin: https://sagapass.com/phpmyadmin"
} else {
    Write-Host "❌ Erreur lors de l'export" -ForegroundColor Red
}
