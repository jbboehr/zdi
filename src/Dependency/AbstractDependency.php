<?php

namespace zdi\Dependency;

use zdi\Utils;

abstract class AbstractDependency
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var boolean
     */
    private $factory;

    /**
     * @var string
     */
    private $name;

    /**
     * AbstractDependency constructor.
     * @param $class
     * @param bool $factory
     * @param null|string $name
     */
    public function __construct($class, $factory = false, $name = null)
    {
        $this->class = $class;
        $this->factory = $factory;
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
     * @return bool
     */
    public function isFactory()
    {
        return $this->factory;
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
        if( $this->name ) {
            return $this->name;
        } else if( $this->class ) {
            return $this->class;
        } else {
            throw new \Exception('Dependency must have a class or name');
        }
    }

    public function getIdentifier()
    {
        if( $this->name ) {
            return Utils::classToIdentifier($this->name);
        } else if( $this->class ) {
            return Utils::classToIdentifier($this->class);
        } else {
            throw new \Exception('Dependency must have a class or name');
        }
    }

    public function getTypeHint()
    {
        if( $this->class ) {
            return $this->class;
        } else {
            return 'scalar';
        }
    }
}
