<?php

namespace zdi\Dependency;

use zdi\Param\ParamInterface;

class DefaultDependency extends AbstractDependency
{
    /**
     * @var ParamInterface[]
     */
    private $params;

    /**
     * @var ParamInterface[]
     */
    private $setters;

    /**
     * Dependency constructor.
     * @param $class
     * @param bool $factory
     * @param string $name
     * @param array $params
     * @param array $setters
     */
    public function __construct($class, $factory = false, $name = null, array $params = array(), array $setters = array())
    {
        parent::__construct($class, $factory, $name);
        $this->params = $params;
        $this->setters = $setters;
    }

    /**
     * @return ParamInterface[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return ParamInterface[]
     */
    public function getSetters()
    {
        return $this->setters;
    }
}
