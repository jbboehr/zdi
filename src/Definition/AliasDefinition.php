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
    public function __construct(string $class, string $alias)
    {
        parent::__construct($class, false);
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias() : string
    {
        return $this->alias;
    }
}
