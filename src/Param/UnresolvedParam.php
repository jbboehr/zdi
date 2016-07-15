<?php

namespace zdi\Param;

use zdi\Param;

class UnresolvedParam implements Param
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     * @param string|null $type
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
