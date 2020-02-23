<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

use function preg_match;

class LowerCaseResolver implements LowerCaseResolverInterface
{
    /**
     * (@inheritDoc)
     */
    public function isWordLowerCase(string $word): bool
    {
        return (bool)preg_match('/^[a-z]/', $word);
    }
}