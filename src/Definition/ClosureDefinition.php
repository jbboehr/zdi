<?php

namespace zdi\Definition;

use Closure;

class ClosureDefinition extends AbstractDefinition
{
    /**
     * @var Closure
     */
    private $closure;

    /**
     * @param $class
     * @param bool $factory
     * @param null|string $name
     * @param Closure $closure
     */
    public function __construct($class, $factory, $name, Closure $closure)
    {
        parent::__construct($class, $factory, $name);
        $this->closure = $closure;
    }

    /**
     * @return Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }
}
