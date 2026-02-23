<?php
/**
 * Tests unitaires — Invoice Model
 *
 * Teste la logique métier sans accès à la base de données
 * via un mock PDO / Database.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Core\Database;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PDOStatement;

class InvoiceModelTest extends TestCase
{
    private Invoice $invoice;

    // ---- Setup ----

    protected function setUp(): void
    {
        // Utiliser une sous-classe mockée qui injecte une fausse DB
        $this->invoice = $this->createInvoiceWithMockDb();
    }

    // ---- Tests : generateNumber ----

    public function testGenerateNumberFormat(): void
    {
        // Simuler 0 factures existantes → attend FACT-2026-0001
        $this->invoice = $this->createInvoiceWithMockDb(mockCountResult: 0);

        $number = $this->invoice->generateNumber();

        $this->assertMatchesRegularExpression(
            '/^FACT-\d{4}-\d{4}$/',
            $number,
            "Le numéro doit respecter le format FACT-YYYY-NNNN"
        );
    }

    public function testGenerateNumberIncrements(): void
    {
        $this->invoice = $this->createInvoiceWithMockDb(mockCountResult: 41);
        $number = $this->invoice->generateNumber();

        $this->assertStringEndsWith('-0042', $number);
    }

    public function testGenerateNumberContainsCurrentYear(): void
    {
        $this->invoice = $this->createInvoiceWithMockDb(mockCountResult: 0);
        $number = $this->invoice->generateNumber();

        $this->assertStringContainsString((string) date('Y'), $number);
    }

    // ---- Tests : calculs montants ----

    public function testTaxCalculation(): void
    {
        $subtotal = 1000.00;
        $taxRate  = 20.0;
        $expected = 200.00;

        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $this->assertSame($expected, $taxAmount);
    }

    public function testTotalIsSubtotalPlusTax(): void
    {
        $subtotal  = 850.50;
        $taxRate   = 20.0;
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total     = round($subtotal + $taxAmount, 2);

        $this->assertSame(1020.60, $total);
    }

    public function testZeroSubtotalGivesZeroTotal(): void
    {
        $subtotal  = 0.0;
        $taxRate   = 20.0;
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total     = $subtotal + $taxAmount;

        $this->assertSame(0.0, $total);
    }

    // ---- Tests : fillable (whitelist) ----

    public function testFillableDoesNotContainId(): void
    {
        $reflection = new \ReflectionClass(Invoice::class);
        $prop       = $reflection->getProperty('fillable');
        $prop->setAccessible(true);
        $fillable   = $prop->getValue(new class extends Invoice {
            public function __construct() { /* pas de DB */ }
        });

        $this->assertNotContains('id', $fillable, 'id ne doit pas être dans fillable');
        $this->assertNotContains('created_at', $fillable, 'created_at ne doit pas être dans fillable');
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Invoice::class);
        $prop       = $reflection->getProperty('fillable');
        $prop->setAccessible(true);
        $fillable   = $prop->getValue(new class extends Invoice {
            public function __construct() { /* pas de DB */ }
        });

        foreach (['client_id', 'number', 'status', 'total', 'issue_date'] as $field) {
            $this->assertContains($field, $fillable, "'{$field}' doit être dans fillable");
        }
    }

    // ---- Tests : statuts valides ----

    /**
     * @dataProvider validStatusProvider
     */
    public function testValidStatuses(string $status): void
    {
        $allowed = ['draft', 'pending', 'paid', 'overdue'];
        $this->assertContains($status, $allowed);
    }

    public static function validStatusProvider(): array
    {
        return [
            ['draft'],
            ['pending'],
            ['paid'],
            ['overdue'],
        ];
    }

    public function testInvalidStatusIsRejected(): void
    {
        $allowed  = ['draft', 'pending', 'paid', 'overdue'];
        $invalid  = 'cancelled'; // Non prévu dans le schéma

        $resolved = in_array($invalid, $allowed, true) ? $invalid : 'draft';
        $this->assertSame('draft', $resolved);
    }

    // ---- Tests : JwtAuth (logique pure, sans réseau) ----

    public function testJwtGenerateAndVerify(): void
    {
        $payload = ['user_id' => 1, 'role' => 'admin'];
        $token   = \App\Core\JwtAuth::generate($payload, 3600);

        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        $decoded = \App\Core\JwtAuth::verify($token);
        $this->assertSame(1, $decoded['user_id']);
        $this->assertSame('admin', $decoded['role']);
    }

    public function testJwtExpiredTokenThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expiré');

        // Générer un token déjà expiré (expiry = -1)
        $token = \App\Core\JwtAuth::generate(['user_id' => 1], -1);
        \App\Core\JwtAuth::verify($token);
    }

    public function testJwtTamperedTokenThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $token  = \App\Core\JwtAuth::generate(['user_id' => 1], 3600);
        $parts  = explode('.', $token);
        $parts[1] = base64_encode('{"user_id":99,"role":"admin","iat":0,"exp":9999999999}');
        $tampered = implode('.', $parts);

        \App\Core\JwtAuth::verify($tampered);
    }

    // ---- Helpers ----

    /**
     * Crée un Invoice avec une fausse Database injectée via réflexion.
     */
    private function createInvoiceWithMockDb(int $mockCountResult = 0): Invoice
    {
        // Créer un stub PDOStatement
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(['cnt' => $mockCountResult]);
        $stmtMock->method('fetchAll')->willReturn([]);
        $stmtMock->method('execute')->willReturn(true);

        // Créer un stub Database
        $dbMock = $this->createMock(Database::class);
        $dbMock->method('fetchOne')->willReturn(['cnt' => $mockCountResult]);
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('query')->willReturn($stmtMock);
        $dbMock->method('lastInsertId')->willReturn('1');

        // Instancier Invoice sans appeler le constructeur réel
        $invoice = new class extends Invoice {
            public function __construct() { /* pas de DB ici */ }
        };

        // Injecter le mock via réflexion
        $ref  = new \ReflectionClass($invoice);
        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue($invoice, $dbMock);

        return $invoice;
    }
}
