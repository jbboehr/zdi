<?php

namespace zdi;

interface Provider
{
    /**
     * @return mixed
     */
    public function get();
}
