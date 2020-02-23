<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Exception;

use UnexpectedValueException;

class TypeException extends UnexpectedValueException
{
    /**
     * Named constructor
     * @param string $name The name of the variable
     * @param string $expectedType The expected type
     * @param mixed $variable The variable with the unexpected type
     * @return TypeException
     */
    public static function fromVariable(string $name, string $expectedType, $variable): self
    {
        $type = is_object($variable) ? sprintf('instance of %s', get_class($variable)) : gettype($variable);
        $message = sprintf('Variable %s has an unexpected type. Expected %s; got %s', $name, $expectedType, $type);
        return new self($message);
    }
}