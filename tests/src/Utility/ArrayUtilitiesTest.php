<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Utility;

use CliffordVickrey\Malarkey\Utility\ArrayUtilities;
use PHPUnit\Framework\TestCase;

class ArrayUtilitiesTest extends TestCase
{
    /** @var ArrayUtilities */
    private $arrayUtilities;

    public function setUp(): void
    {
        $this->arrayUtilities = new ArrayUtilities();
    }

    public function testComputeGreatestCommonDivisor(): void
    {
        $this->assertEquals(0, $this->arrayUtilities->computeGreatestCommonDivisor([]));
        $this->assertEquals(2, $this->arrayUtilities->computeGreatestCommonDivisor([2]));
        $this->assertEquals(3, $this->arrayUtilities->computeGreatestCommonDivisor([3, 6, 9]));
        $this->assertEquals(1, $this->arrayUtilities->computeGreatestCommonDivisor([9, 10]));
    }

    public function testDivideNumbersByCommonFactor(): void
    {
        $this->assertEquals(['a' => 0], $this->arrayUtilities->divideValuesByGreatestCommonFactor(['a' => 0]));
        $this->assertEquals(
            ['a' => 1, 'b' => 2, 'c' => 3],
            $this->arrayUtilities->divideValuesByGreatestCommonFactor(['a' => 7, 'b' => 14, 'c' => 21])
        );
    }

    public function testFilterOutIntegersLessThanOne(): void
    {
        $this->assertEquals(
            ['c' => 1],
            $this->arrayUtilities->filterOutIntegersLessThanOne(['a' => -1, 'b' => 0, 'c' => 1])
        );
    }
}
