<?php

namespace zdi;

use ArrayAccess;
use Interop\Container\ContainerInterface;

interface Container extends ArrayAccess, ContainerInterface
{
    /**
     * @return string[]
     */
    public function keys();
}
