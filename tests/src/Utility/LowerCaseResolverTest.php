<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Utility;

use CliffordVickrey\Malarkey\Utility\LowerCaseResolver;
use PHPUnit\Framework\TestCase;

class LowerCaseResolverTest extends TestCase
{
    public function testIsLowerCase(): void
    {
        $resolver = new LowerCaseResolver();
        $this->assertEquals(true, $resolver->isWordLowerCase('bill stickers will be prosecuted'));
        $this->assertEquals(false, $resolver->isWordLowerCase('Bill Stickers is innocent!'));
    }

}