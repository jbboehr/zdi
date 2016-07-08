<?php

namespace zdi\Dependency;

class AliasDependency extends AbstractDependency
{
    /**
     * @var string
     */
    private $alias;

    /**
     * AliasDependency constructor.
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
