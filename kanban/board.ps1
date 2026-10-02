# Zeigt das Kanban-Board im Terminal: powershell -File kanban/board.ps1
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$columns = Get-ChildItem -Path $PSScriptRoot -Directory | Sort-Object Name

foreach ($column in $columns) {
    $cards = Get-ChildItem -Path $column.FullName -Filter 'MB-*.md' | Sort-Object Name
    Write-Host ''
    Write-Host ("== {0} ({1}) ==" -f $column.Name, $cards.Count) -ForegroundColor Green
    foreach ($card in $cards) {
        $lines = Get-Content -Path $card.FullName -Encoding UTF8
        $title = ($lines[0] -replace '^#\s*', '')
        # Nur ASCII im Muster, weil Windows PowerShell 5 das Skript nicht als UTF-8 liest
        $row = $lines | Where-Object { $_ -match '^\| Zust\S+ \|' } | Select-Object -First 1
        $who = if ($row) { ($row -split '\|')[2].Trim() } else { '' }
        Write-Host ("  {0,-55} {1}" -f $title, $who)
    }
}
Write-Host ''
