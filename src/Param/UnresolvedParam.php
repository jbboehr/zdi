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
     * @var string
     */
    private $type;

    /**
     * @param string $name
     * @param string|null $type
     */
    public function __construct($name, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }
}
