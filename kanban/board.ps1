# Zeigt das Kanban-Board im Terminal: powershell -File kanban/board.ps1
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$columns = Get-ChildItem -Path $PSScriptRoot -Directory | Sort-Object Name

# Liest ein Feld aus der Kopftabelle einer Karte.
# Nur ASCII im Muster, weil Windows PowerShell 5 das Skript nicht als UTF-8 liest.
function Get-Field($lines, $pattern) {
    $row = $lines | Where-Object { $_ -match "^\| $pattern \|" } | Select-Object -First 1
    if ($row) { return (($row -split '\|')[2] -replace '\*', '').Trim() }
    return ''
}

foreach ($column in $columns) {
    $cards = Get-ChildItem -Path $column.FullName -Filter 'MB-*.md' | Sort-Object Name
    Write-Host ''
    Write-Host ("== {0} ({1}) ==" -f $column.Name, $cards.Count) -ForegroundColor Green
    foreach ($card in $cards) {
        $lines = Get-Content -Path $card.FullName -Encoding UTF8
        $title = ($lines[0] -replace '^#\s*', '')
        if ($title.Length -gt 58) { $title = $title.Substring(0, 55) + '...' }
        $who = Get-Field $lines 'Zust\S+'
        # Nur das Datum zeigen: alles ab Klammer, Komma oder Gedankenstrich (–) abschneiden
        $due = (Get-Field $lines 'F\S+llig') -replace '\s*(\(|,|\s–).*$', ''
        Write-Host ("  {0,-58} {1,-28} {2}" -f $title, $who, $due)
    }
}
Write-Host ''
