<?php

namespace zdi\Definition;

use zdi\Param;

class DataDefinition extends AbstractDefinition
{
    /**
     * @var Param[]
     */
    private $params;

    /**
     * @var Param[]
     */
    private $setters;

    /**
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
     * @return Param[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return Param[]
     */
    public function getSetters()
    {
        return $this->setters;
    }
}
