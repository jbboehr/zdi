<?php

namespace zdi\Param;

use zdi\Param;

class NamedParam implements Param
{
    /**
     * @var string
     */
    private $name;

    /**
     * NamedParam constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
