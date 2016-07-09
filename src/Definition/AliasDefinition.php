<?php

namespace zdi\Definition;

class AliasDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @param $class
     * @param bool $alias
     */
    public function __construct($class, $alias)
    {
        parent::__construct($class, false);
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
