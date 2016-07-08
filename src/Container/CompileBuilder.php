<?php

namespace zdi\Container;

use zdi\Compiler\Compiler;

class CompileBuilder extends Builder
{
    private $file;

    private $namespace;

    private $class;

    private $ttl;

    public function __construct($file, $namespace, $class)
    {
        $this->file = $file;
        $this->namespace = $namespace;
        $this->class = $class;
        $this->ttl = 0;
    }

    public function ttl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function build()
    {
        if( !$this->isValid() ) {
            $compiler = new Compiler($this->getDependencies(), $this->namespace, $this->class);
            $code = $compiler->compile();
            file_put_contents($this->file, $code);
        }
        require_once $this->file;
        $fullClass = $this->namespace . '\\' . $this->class;
        return new $fullClass;
    }

    public function isReady()
    {
        if( $this->ttl < 0 ) {
            return true;
        } else if( $this->ttl === 0 ) {
            return false;
        } else if( !file_exists($this->file) ) {
            return false;
        } else if( filemtime($this->file) + $this->ttl < time() ) {
            return false;
        } else {
            return true;
        }
    }

    private function isValid()
    {
        if( !file_exists($this->file) ) {
            return false;
        }

        $mtime = filemtime($this->file);

        foreach( $this->getDependencies() as $dependency ) {
            $class = $dependency->getClass();
            if( !class_exists($class, true) ) {
                continue;
            }
            $reflectionClass = new \ReflectionClass($class);
            if( filemtime($reflectionClass->getFileName()) > $mtime ) {
                return false;
            }
        }

        return true;
    }
}
