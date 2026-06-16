<?php
declare(strict_types=1);

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    /** @var list<string> */
    private array $failures = [];

    /** @var list<string> */
    private array $skippedTests = [];

    public function test(string $name, callable $fn): void
    {
        try {
            $fn($this);
            $this->passed++;
            echo "  ✓ {$name}\n";
        } catch (SkipTest $e) {
            $this->skipped++;
            $this->skippedTests[] = "{$name}: {$e->getMessage()}";
            echo "  ○ {$name} (ข้าม: {$e->getMessage()})\n";
        } catch (Throwable $e) {
            $this->failed++;
            $this->failures[] = "{$name}: {$e->getMessage()}";
            echo "  ✗ {$name}\n";
            echo "    {$e->getMessage()}\n";
        }
    }

    public function assertTrue(bool $cond, string $msg = 'assertion failed'): void
    {
        if (!$cond) {
            throw new RuntimeException($msg);
        }
    }

    public function assertFalse(bool $cond, string $msg = 'expected false'): void
    {
        $this->assertTrue(!$cond, $msg);
    }

    public function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            $detail = $msg !== '' ? $msg . ' — ' : '';
            throw new RuntimeException($detail . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    public function assertContains(string $needle, string $haystack, string $msg = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            $detail = $msg !== '' ? $msg . ' — ' : '';
            throw new RuntimeException($detail . "ไม่พบข้อความ \"{$needle}\"");
        }
    }

    public function assertNotContains(string $needle, string $haystack, string $msg = ''): void
    {
        if (str_contains($haystack, $needle)) {
            $detail = $msg !== '' ? $msg . ' — ' : '';
            throw new RuntimeException($detail . "ไม่ควรพบข้อความ \"{$needle}\"");
        }
    }

    public function assertFileExists(string $path, string $msg = ''): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(($msg !== '' ? $msg . ' — ' : '') . "ไม่พบไฟล์ {$path}");
        }
    }

    public function skip(string $reason): void
    {
        throw new SkipTest($reason);
    }

    public function summary(): int
    {
        echo "\n";
        echo "══════════════════════════════════════\n";
        echo "ผ่าน: {$this->passed}  |  ล้มเหลว: {$this->failed}  |  ข้าม: {$this->skipped}\n";
        if ($this->failures !== []) {
            echo "\nรายการที่ล้มเหลว:\n";
            foreach ($this->failures as $f) {
                echo "  • {$f}\n";
            }
        }
        if ($this->skippedTests !== []) {
            echo "\nรายการที่ข้าม:\n";
            foreach ($this->skippedTests as $s) {
                echo "  • {$s}\n";
            }
        }
        echo "══════════════════════════════════════\n";
        return $this->failed > 0 ? 1 : 0;
    }
}

final class SkipTest extends RuntimeException
{
}
