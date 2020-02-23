<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Exception;

use CliffordVickrey\Malarkey\Exception\TypeException;
use PHPStan\Testing\TestCase;
use stdClass;

class TypeExceptionTest extends TestCase
{
    public function testFromVariable(): void
    {
        $e = TypeException::fromVariable('blah', 'string', false);
        $this->assertEquals('Variable blah has an unexpected type. Expected string; got boolean', $e->getMessage());

        $e = TypeException::fromVariable('blah', 'string', new stdClass());
        $this->assertEquals(
            'Variable blah has an unexpected type. Expected string; got instance of stdClass',
            $e->getMessage()
        );
    }
}
