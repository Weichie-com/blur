<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Models\RecognizerResult;

class RecognizerResultTest extends TestCase
{
    public function testCreateValidResult(): void
    {
        $result = new RecognizerResult('EMAIL', 0, 10, 0.95);

        $this->assertEquals('EMAIL', $result->entityType);
        $this->assertEquals(0, $result->start);
        $this->assertEquals(10, $result->end);
        $this->assertEquals(0.95, $result->score);
        $this->assertEquals(10, $result->length());
    }

    public function testInvalidNegativeStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RecognizerResult('EMAIL', -1, 10, 0.95);
    }

    public function testInvalidStartGreaterThanEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RecognizerResult('EMAIL', 10, 5, 0.95);
    }

    public function testInvalidScoreTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RecognizerResult('EMAIL', 0, 10, -0.1);
    }

    public function testInvalidScoreTooHigh(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RecognizerResult('EMAIL', 0, 10, 1.1);
    }

    public function testEqualIndices(): void
    {
        $result1 = new RecognizerResult('EMAIL', 0, 10, 0.95);
        $result2 = new RecognizerResult('EMAIL', 0, 10, 0.85);

        $this->assertTrue($result1->equalIndices($result2));
    }

    public function testContainedIn(): void
    {
        $inner = new RecognizerResult('EMAIL', 5, 10, 0.95);
        $outer = new RecognizerResult('TEXT', 0, 15, 0.85);

        $this->assertTrue($inner->containedIn($outer));
        $this->assertFalse($outer->containedIn($inner));
    }

    public function testContains(): void
    {
        $inner = new RecognizerResult('EMAIL', 5, 10, 0.95);
        $outer = new RecognizerResult('TEXT', 0, 15, 0.85);

        $this->assertTrue($outer->contains($inner));
        $this->assertFalse($inner->contains($outer));
    }

    public function testOverlaps(): void
    {
        $result1 = new RecognizerResult('EMAIL', 0, 10, 0.95);
        $result2 = new RecognizerResult('TEXT', 5, 15, 0.85);
        $result3 = new RecognizerResult('URL', 20, 30, 0.90);

        $this->assertTrue($result1->overlaps($result2));
        $this->assertFalse($result1->overlaps($result3));
    }

    public function testHasConflict(): void
    {
        // Same indices, lower score = conflict
        $result1 = new RecognizerResult('EMAIL', 0, 10, 0.85);
        $result2 = new RecognizerResult('EMAIL', 0, 10, 0.95);

        $this->assertTrue($result1->hasConflict($result2));
        $this->assertFalse($result2->hasConflict($result1));

        // Contained in = conflict
        $inner = new RecognizerResult('EMAIL', 5, 10, 0.95);
        $outer = new RecognizerResult('TEXT', 0, 15, 0.85);

        $this->assertTrue($inner->hasConflict($outer));
    }
}
