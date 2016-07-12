<?php

namespace zdi\Definition;

use Closure;
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
     * @param string $class
     * @param boolean $factory
     * @param null|string $name
     * @param Closure $closure
     * @param Param[] $params
     */
    public function __construct($class, $factory, $name, Closure $closure, array $params = array())
    {
        parent::__construct($class, $factory, $name);
        $this->closure = $closure;
        $this->params = $params;
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
}
