<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/csv.php';

/**
 * CSV-Export (MB-060): Formel-Injection abfangen, Zahlen für deutsches Excel.
 */
final class CsvTest extends TestCase
{
    public function testFormelnWerdenEntschaerft(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", csvText('=HYPERLINK("http://x")'));
        $this->assertSame("'+49 170", csvText('+49 170'));
        $this->assertSame("'-2+3", csvText('-2+3'));
        $this->assertSame("'@SUMME(A1)", csvText('@SUMME(A1)'));
    }

    public function testNormalerTextBleibtUnveraendert(): void
    {
        $this->assertSame('Kaffeebohnen Rösterei', csvText('Kaffeebohnen Rösterei'));
        $this->assertSame('', csvText(null));
    }

    public function testZahlenMitKommaOhneTausenderpunkt(): void
    {
        $this->assertSame('1234,50', csvZahl(1234.5));
        $this->assertSame('-1000,00', csvZahl(-1000));
    }
}
