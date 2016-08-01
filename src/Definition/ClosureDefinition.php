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
     * @param Closure $closure
     * @param array $params
     * @param string $class
     * @param string $name
     * @param integer $flags
     */
    public function __construct(Closure $closure, array $params, $class, $name, $flags)
    {
        $this->closure = $closure;
        $this->params = $params;

        parent::__construct($class, $name, $flags);

        if( $this->hasInjectionPointParam() && !$this->isFactory() ) {
            throw new Exception\DomainException('Definition with injection point must be marked as factory');
        }
    }

    /**
     * @return Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * @return Param[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return boolean
     */
    public function hasInjectionPointParam()
    {
        foreach( $this->params as $param ) {
            if( $param instanceof Param\InjectionPointParam ) {
                return true;
            }
        }
        return false;
    }
}
