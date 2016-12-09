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

    /**
     * @var boolean
     */
    private $isOptional = false;

    /**
     * ClassParam constructor.
     * @param string $class
     * @param boolean $isOptional
     */
    public function __construct(string $class, bool $isOptional = false)
    {
        $this->class = $class;
        $this->isOptional = $isOptional;
    }

    /**
     * @return string
     */
    public function getClass() : string
    {
        return $this->class;
    }

    /**
     * @return boolean
     */
    public function isOptional() : bool
    {
        return $this->isOptional;
    }
}
