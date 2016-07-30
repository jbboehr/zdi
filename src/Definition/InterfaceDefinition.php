<?php

namespace zdi\Definition;

use zdi\Exception;
use zdi\Param;

class InterfaceDefinition extends AbstractDefinition
{
    private $setters;

    static public function fromString($string)
    {
        $setters = array();
        $r = new \ReflectionClass($string);
        foreach( $r->getMethods(\ReflectionMethod::IS_PUBLIC) as $method ) {
            if( $method->getNumberOfParameters() !== 1 ) {
                throw new Exception\DomainException('Setter interface must only have one argument');
            }
            $parameters = $method->getParameters();
            $parameter = $parameters[0];
            $class = $parameter->getClass();
            if( !$class ) {
                throw new Exception\DomainException('Parameter must declare a type');
            }
            $setters[$method->getName()] =  new Param\ClassParam($class->getName());
        }
        return new self($string, null, 0, $setters);
    }

    public function __construct($class, $name, $flags, $setters)
    {
        parent::__construct($class, $name, $flags);
        $this->setters = $setters;
    }

    public function getSetters()
    {
        return $this->setters;
    }
}
