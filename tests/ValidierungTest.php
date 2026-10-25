<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die zentrale Eingabeprüfung (MB-051, src/validierung.php).
 * Reine Funktionen ohne Datenbank.
 */
final class ValidierungTest extends TestCase
{
    private array $budget = ['gueltig_von' => '2026-01-01', 'gueltig_bis' => '2026-12-31', 'betrag' => '100.00'];

    public function testBetragInVerschiedenenSchreibweisen(): void
    {
        $this->assertSame(12.5, leseBetrag('12.50'));
        $this->assertSame(12.5, leseBetrag('12,50'));
        $this->assertSame(1234.56, leseBetrag('1.234,56'));
        $this->assertSame(249.9, leseBetrag(' 249,90 € '));
        $this->assertSame(100.0, leseBetrag('100'));
    }

    public function testUngueltigeBetraegeWerdenAbgelehnt(): void
    {
        $this->assertNull(leseBetrag(''));
        $this->assertNull(leseBetrag('abc'));
        $this->assertNull(leseBetrag('12.345'));   // mehr als 2 Nachkommastellen
        $this->assertNull(leseBetrag('1e5'));
        $this->assertNull(leseBetrag(['12']));
    }

    public function testDatumWirdStrengGeprueft(): void
    {
        $this->assertTrue(istGueltigesDatum('2026-02-28'));
        $this->assertFalse(istGueltigesDatum('2026-02-31'));
        $this->assertFalse(istGueltigesDatum('31.12.2026'));
        $this->assertFalse(istGueltigesDatum(''));
    }

    public function testGueltigeAusgabeHatKeineFehler(): void
    {
        $ergebnis = pruefeAusgabe(
            ['betrag' => '49,90', 'datum' => '2026-10-20', 'beschreibung' => ' Matten '],
            $this->budget,
            '2026-10-25'
        );

        $this->assertSame([], $ergebnis['fehler']);
        $this->assertSame(49.9, $ergebnis['werte']['betrag']);
        $this->assertSame('Matten', $ergebnis['werte']['beschreibung']);
    }

    public function testAusgabeInDerZukunftWirdAbgelehnt(): void
    {
        $ergebnis = pruefeAusgabe(['betrag' => '10', 'datum' => '2026-10-26', 'beschreibung' => 'x'], $this->budget, '2026-10-25');

        $this->assertArrayHasKey('datum', $ergebnis['fehler']);
    }

    public function testAusgabeAusserhalbDerLaufzeitWirdAbgelehnt(): void
    {
        $ergebnis = pruefeAusgabe(['betrag' => '10', 'datum' => '2025-12-31', 'beschreibung' => 'x'], $this->budget, '2026-10-25');

        $this->assertStringContainsString('vor dem Budgetbeginn', $ergebnis['fehler']['datum']);
    }

    public function testNullUndZuGrosseBetraegeWerdenAbgelehnt(): void
    {
        $null = pruefeAusgabe(['betrag' => '0', 'datum' => '2026-10-01', 'beschreibung' => 'x'], $this->budget, '2026-10-25');
        $riesig = pruefeAusgabe(['betrag' => '100000000', 'datum' => '2026-10-01', 'beschreibung' => 'x'], $this->budget, '2026-10-25');

        $this->assertArrayHasKey('betrag', $null['fehler']);
        $this->assertArrayHasKey('betrag', $riesig['fehler']);
    }

    public function testKorrekturBrauchtGrundUndNeuenBetrag(): void
    {
        $ohneAenderung = pruefeKorrektur(['betrag' => '100,00', 'grund' => 'Tippfehler'], $this->budget);
        $ohneGrund = pruefeKorrektur(['betrag' => '90', 'grund' => '  '], $this->budget);

        $this->assertArrayHasKey('betrag', $ohneAenderung['fehler']);
        $this->assertArrayHasKey('grund', $ohneGrund['fehler']);
    }

    public function testBudgetEndeVorBeginnWirdAbgelehnt(): void
    {
        $ergebnis = pruefeBudget([
            'name' => 'Test', 'bereich' => 'CAFE', 'soll_betrag' => '500',
            'gueltig_von' => '2026-12-01', 'gueltig_bis' => '2026-11-01', 'owner_id' => '7',
        ], [7]);

        $this->assertSame(['gueltig_bis'], array_keys($ergebnis['fehler']));
    }

    public function testBudgetNurFuerEchteOwner(): void
    {
        $ergebnis = pruefeBudget([
            'name' => 'Test', 'bereich' => 'CAFE', 'soll_betrag' => '500',
            'gueltig_von' => '2026-01-01', 'gueltig_bis' => '2026-12-31', 'owner_id' => '1',
        ], [3, 4, 5]);

        $this->assertArrayHasKey('owner_id', $ergebnis['fehler']);
    }

    public function testPasswortRegeln(): void
    {
        $this->assertNotNull(pruefePasswort('kurz'));
        $this->assertNotNull(pruefePasswort(str_repeat('a', 73)));
        $this->assertNotNull(pruefePasswort('geheim123', 'geheim124'));
        $this->assertNull(pruefePasswort('geheim123', 'geheim123'));
    }

    public function testBenutzernameMussFreiUndSauberSein(): void
    {
        $vergeben = fn (string $name): bool => $name === 'studio';

        $doppelt = pruefeNeuenBenutzer(['anzeigename' => 'X', 'username' => 'Studio', 'rolle' => 'OWNER', 'passwort' => 'geheim123'], $vergeben);
        $sonderzeichen = pruefeNeuenBenutzer(['anzeigename' => 'X', 'username' => 'a b', 'rolle' => 'OWNER', 'passwort' => 'geheim123'], $vergeben);
        $ok = pruefeNeuenBenutzer(['anzeigename' => 'Yoga', 'username' => 'Yoga', 'rolle' => 'OWNER', 'passwort' => 'geheim123'], $vergeben);

        $this->assertStringContainsString('vergeben', $doppelt['fehler']['username']);
        $this->assertArrayHasKey('username', $sonderzeichen['fehler']);
        $this->assertSame([], $ok['fehler']);
        $this->assertSame('yoga', $ok['werte']['username']);
    }
}
