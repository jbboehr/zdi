<?php

namespace zdi\Definition;

use Closure;
use zdi\Exception;
use zdi\Param;

class ClosureDefinition extends AbstractDefinition
{
    /**
     * @var Closure
     */
    private $closure;

    /**
     * @var Param[]
     */
    private $params;

    /**
     * @var array
     */
    private $setters;

    /**
     * @param Closure $closure
     * @param array $params
     * @param array $setters
     * @param string $class
     * @param string $name
     * @param integer $flags
     */
    public function __construct(Closure $closure, array $params, array $setters, $class, $name, $flags)
    {
        $this->closure = $closure;
        $this->params = $params;
        $this->setters = $setters;

        parent::__construct($class, $name, $flags);

        if( $this->hasInjectionPointParam() && !$this->isFactory() ) {
            throw new Exception\DomainException('Definition with injection point must be marked as factory');
        }
    }

    /**
     * @return Closure
     */
    public function getClosure() : Closure
    {
        return $this->closure;
    }

    /**
     * @return Param[]
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getSetters() : array
    {
        return $this->setters;
    }

    /**
     * @return boolean
     */
    public function hasInjectionPointParam() : bool
    {
        foreach( $this->params as $param ) {
            if( $param instanceof Param\InjectionPointParam ) {
                return true;
            }
        }
        return false;
    }
}
