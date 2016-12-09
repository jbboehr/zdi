<?php

namespace zdi\Definition;

use zdi\Exception;
use zdi\Param;

class InterfaceDefinition extends AbstractDefinition
{
    private $setters = array();

    /**
     * @param string $string
     * @return InterfaceDefinition
     */
    static public function fromString($string) : InterfaceDefinition
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

    /**
     * InterfaceDefinition constructor.
     * @param null|string $class
     * @param null|string $name
     * @param int $flags
     * @param array $setters
     */
    public function __construct($class, $name, int $flags = 0, array $setters = array())
    {
        parent::__construct($class, $name, $flags);
        $this->setters = $setters;
    }

    /**
     * @return array
     */
    public function getSetters() : array
    {
        return $this->setters;
    }
}
