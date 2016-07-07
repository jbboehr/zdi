<?php

namespace zdi\Dependency;

use Closure;

class ClosureDependency extends AbstractDependency
{
    /**
     * @var Closure
     */
    private $closure;

    /**
     * ClosureDependency constructor.
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
