<?php

namespace zdi;

use Closure;

abstract class AbstractContainer implements ContainerInterface
{
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }
}
