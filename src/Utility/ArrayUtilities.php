<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

use Closure;
use function array_combine;
use function array_fill;
use function array_filter;
use function array_keys;
use function array_map;
use function array_reduce;
use function call_user_func;
use function count;

class ArrayUtilities
{
    /** @var Closure */
    private $divisionClosure;
    /** @var Closure */
    private $isIntegerGreaterThanOneClosure;
    /** @var Closure */
    private $greatestCommonDivisorClosure;

    public function __construct()
    {
        $this->divisionClosure = function (int $a, int $b): int {
            return $a / $b;
        };

        $this->isIntegerGreaterThanOneClosure = function (int $value): bool {
            return $value > 0;
        };

        $this->greatestCommonDivisorClosure = function (int $a, int $b): int {
            return $b ? call_user_func($this->greatestCommonDivisorClosure, $b, $a % $b) : $a;
        };
    }

    /**
     * @param array<string, int> $values
     * @return array<string, int>
     */
    public function divideValuesByGreatestCommonFactor(array $values): array
    {
        $greatestCommonDivisor = $this->computeGreatestCommonDivisor($values);

        if (!$greatestCommonDivisor) {
            return $values;
        }

        return array_combine(
            array_keys($values),
            array_map($this->divisionClosure, $values, array_fill(0, count($values), $greatestCommonDivisor))
        ) ?: [];
    }

    /**
     * @param array<mixed, int> $values
     * @return int
     */
    public function computeGreatestCommonDivisor(array $values): int
    {
        return array_reduce($values, $this->greatestCommonDivisorClosure, 0);
    }

    /**
     * @param array<string, int> $values
     * @return array<string, int>
     */
    public function filterOutIntegersLessThanOne(array $values): array
    {
        return array_filter($values, $this->isIntegerGreaterThanOneClosure);
    }
}
