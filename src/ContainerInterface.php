<?php

namespace zdi;

use ArrayAccess;

interface ContainerInterface extends ArrayAccess
{
    public function has($key);

    public function get($key);
}
