<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Utility;

use CliffordVickrey\Malarkey\Utility\EndOfSentenceResolver;
use PHPUnit\Framework\TestCase;

class EndOfSentenceResolverTest extends TestCase
{
    public function testIsEndOfSentence(): void
    {
        $resolver = new EndOfSentenceResolver();
        $this->assertFalse($resolver->isEndOfSentence("I'd"));
        $this->assertFalse($resolver->isEndOfSentence('buy'));
        $this->assertFalse($resolver->isEndOfSentence('that'));
        $this->assertFalse($resolver->isEndOfSentence('for'));
        $this->assertFalse($resolver->isEndOfSentence('a'));
        $this->assertTrue($resolver->isEndOfSentence('dollar!'));
        $this->assertTrue($resolver->isEndOfSentence('dollar?'));
        $this->assertTrue($resolver->isEndOfSentence('dollar.'));
        $this->assertTrue($resolver->isEndOfSentence('dollar."'));
    }

}
