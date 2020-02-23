<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

interface LowerCaseResolverInterface
{
    /**
     * @param string $word
     * @return bool
     */
    public function isWordLowerCase(string $word): bool;
}