<?php

namespace zdi\Container;

class PrecompiledBuilder extends Builder
{
    private $file;

    private $namespace;

    private $class;

    public function __construct($file, $namespace, $class)
    {
        $this->file = $file;
        $this->namespace = $namespace;
        $this->class = $class;
    }

    public function build()
    {
        require_once $this->file;
        $fullClass = $this->namespace . '\\' . $this->class;
        return new $fullClass;
    }

    public function isReady()
    {
        return true;
    }
}
