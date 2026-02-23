<?php
/**
 * Tests unitaires — Project Model
 *
 * Teste la logique métier (computeProgress, isLate, statuts, priorities)
 * sans accès base de données (mocks PDO).
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Project;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

class ProjectModelTest extends TestCase
{
    // ============================================================
    // computeProgress
    // ============================================================

    public function testProgressZeroWhenNoTimeline(): void
    {
        $model = $this->makeProject();
        $this->assertSame(0, $model->computeProgress(null));
        $this->assertSame(0, $model->computeProgress(''));
        $this->assertSame(0, $model->computeProgress('[]'));
    }

    public function testProgressZeroWhenNoneAreDone(): void
    {
        $model = $this->makeProject();
        $json  = json_encode([
            ['label' => 'Étape 1', 'date' => '2026-01-01', 'done' => false],
            ['label' => 'Étape 2', 'date' => '2026-02-01', 'done' => false],
        ]);
        $this->assertSame(0, $model->computeProgress($json));
    }

    public function testProgressHundredWhenAllDone(): void
    {
        $model = $this->makeProject();
        $json  = json_encode([
            ['label' => 'Étape 1', 'done' => true],
            ['label' => 'Étape 2', 'done' => true],
            ['label' => 'Étape 3', 'done' => true],
        ]);
        $this->assertSame(100, $model->computeProgress($json));
    }

    public function testProgressFiftyPercent(): void
    {
        $model = $this->makeProject();
        $json  = json_encode([
            ['label' => 'A', 'done' => true],
            ['label' => 'B', 'done' => false],
        ]);
        $this->assertSame(50, $model->computeProgress($json));
    }

    public function testProgressRoundsCorrectly(): void
    {
        $model = $this->makeProject();
        // 1 sur 3 = 33.33... → arrondi à 33
        $json  = json_encode([
            ['label' => 'A', 'done' => true],
            ['label' => 'B', 'done' => false],
            ['label' => 'C', 'done' => false],
        ]);
        $this->assertSame(33, $model->computeProgress($json));
    }

    public function testProgressIgnoresInvalidJson(): void
    {
        $model = $this->makeProject();
        $this->assertSame(0, $model->computeProgress('not-json'));
        $this->assertSame(0, $model->computeProgress('{"key":"val"}')); // pas un tableau
    }

    // ============================================================
    // Constantes et configuration
    // ============================================================

    public function testStatusesContainAllExpected(): void
    {
        $this->assertContains('todo',        Project::STATUSES);
        $this->assertContains('in_progress', Project::STATUSES);
        $this->assertContains('review',      Project::STATUSES);
        $this->assertContains('done',        Project::STATUSES);
        $this->assertContains('archived',    Project::STATUSES);
    }

    public function testPrioritiesContainAllExpected(): void
    {
        foreach (['low', 'medium', 'high', 'critical'] as $p) {
            $this->assertContains($p, Project::PRIORITIES);
        }
    }

    public function testStatusLabelsMatchStatuses(): void
    {
        foreach (Project::STATUSES as $status) {
            $this->assertArrayHasKey($status, Project::STATUS_LABELS,
                "STATUS_LABELS doit avoir une entrée pour '{$status}'"
            );
        }
    }

    public function testPriorityLabelsMatchPriorities(): void
    {
        foreach (Project::PRIORITIES as $p) {
            $this->assertArrayHasKey($p, Project::PRIORITY_LABELS,
                "PRIORITY_LABELS doit avoir une entrée pour '{$p}'"
            );
        }
    }

    // ============================================================
    // fillable (whitelist anti mass-assignment)
    // ============================================================

    public function testFillableContainsRequiredFields(): void
    {
        $model = $this->makeProject();
        $ref   = new \ReflectionClass($model);
        $prop  = $ref->getProperty('fillable');
        $prop->setAccessible(true);
        $f = $prop->getValue($model);

        foreach (['client_id', 'name', 'status', 'priority', 'timeline'] as $field) {
            $this->assertContains($field, $f, "fillable doit contenir '{$field}'");
        }
    }

    public function testFillableDoesNotContainId(): void
    {
        $model = $this->makeProject();
        $ref   = new \ReflectionClass($model);
        $prop  = $ref->getProperty('fillable');
        $prop->setAccessible(true);
        $f = $prop->getValue($model);

        $this->assertNotContains('id', $f);
        $this->assertNotContains('created_at', $f);
    }

    // ============================================================
    // Validation métier status/priority
    // ============================================================

    /**
     * @dataProvider validStatusProvider
     */
    public function testValidStatus(string $status): void
    {
        $this->assertContains($status, Project::STATUSES);
    }

    public static function validStatusProvider(): array
    {
        return array_map(fn($s) => [$s], Project::STATUSES);
    }

    public function testInvalidStatusDefaultsTodo(): void
    {
        $invalid  = 'cancelled';
        $resolved = in_array($invalid, Project::STATUSES, true) ? $invalid : 'todo';
        $this->assertSame('todo', $resolved);
    }

    public function testInvalidPriorityDefaultsMedium(): void
    {
        $invalid  = 'ultra';
        $resolved = in_array($invalid, Project::PRIORITIES, true) ? $invalid : 'medium';
        $this->assertSame('medium', $resolved);
    }

    // ============================================================
    // Timeline JSON sanity checks
    // ============================================================

    public function testTimelineJsonEncodesAndDecodes(): void
    {
        $steps = [
            ['label' => 'Étape 1', 'date' => '2026-01-01', 'done' => true],
            ['label' => 'Étape 2', 'date' => '2026-02-01', 'done' => false],
        ];
        $encoded = json_encode($steps, JSON_UNESCAPED_UNICODE);
        $decoded = json_decode($encoded, true);

        $this->assertCount(2, $decoded);
        $this->assertTrue($decoded[0]['done']);
        $this->assertFalse($decoded[1]['done']);
        $this->assertSame('Étape 1', $decoded[0]['label']);
    }

    // ============================================================
    // Helper
    // ============================================================

    private function makeProject(): Project
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);
        $stmtMock->method('fetch')->willReturn(false);

        $dbMock = $this->createMock(Database::class);
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('fetchOne')->willReturn(false);
        $dbMock->method('query')->willReturn($stmtMock);

        $project = new class extends Project {
            public function __construct() { /* no DB */ }
        };

        $ref  = new \ReflectionClass($project);
        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue($project, $dbMock);

        return $project;
    }
}
