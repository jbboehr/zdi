<?php

namespace zdi\Param;

use zdi\Param;
use zdi\Utils;

class ClassParam implements Param
{
    /**
     * @var string
     */
    private $class;

    private $isOptional = false;

    /**
     * ClassParam constructor.
     * @param string $class
     * @param boolean $isOptional
     */
    public function __construct($class, $isOptional = false)
    {
        $this->class = $class;
        $this->isOptional = $isOptional;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return Utils::classToIdentifier($this->class);
    }

    public function isOptional()
    {
        return $this->isOptional;
    }
}
