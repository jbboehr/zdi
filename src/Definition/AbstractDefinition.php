<?php

namespace zdi\Definition;

use zdi\Definition;
use zdi\Utils;

abstract class AbstractDefinition implements Definition
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var integer
     */
    private $flags;

    /**
     * @var string
     */
    private $name;

    /**
     * @param $class
     * @param string|null $name
     * @param integer $flags
     */
    public function __construct($class, $name = null, $flags = 0)
    {
        $this->class = $class;
        $this->flags = $flags;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return boolean
     */
    public function isFactory()
    {
        return (boolean) ($this->flags & Definition::FACTORY);
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        $name = $this->getName();
        $class = $this->getClass();
        return $name ?: $class;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        $name = $this->getName();
        $class = $this->getClass();
        if( $name ) {
            return Utils::classToIdentifier($name);
        } else /*if( $this->class )*/ {
            return Utils::classToIdentifier($class);
        }
    }

    /**
     * @return string
     */
    public function getTypeHint()
    {
        $class = $this->getClass();
        if( $class ) {
            return '\\' . $class;
        } else {
            return 'scalar';
        }
    }
}
