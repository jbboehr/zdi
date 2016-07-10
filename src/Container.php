<?php

namespace zdi;

use ArrayAccess;

interface Container extends ArrayAccess
{
    /**
     * @param string $key
     * @return boolean
     */
    public function has($key);

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * @return string[]
     */
    public function keys();
}
