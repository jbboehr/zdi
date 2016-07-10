<?php

namespace zdi\Container;

use zdi\Container;
use zdi\Compiler\Compiler;
use zdi\Definition;
use zdi\Definition\DefinitionBuilder;
use zdi\Exception;

class ContainerBuilder
{
    /**
     * @var array
     */
    private $blacklist = array();

    /**
     * @var string
     */
    private $className;

    /**
     * @var Definition[]
     */
    private $definitions = array();

    /**
     * @var string
     */
    private $file;

    /**
     * @var boolean
     */
    private $precompiled;

    /**
     * @var boolean
     */
    private $stat;

    /**
     * @var integer
     */
    private $ttl;

    /**
     * Builder constructor.
     */
    public function __construct()
    {
        // Add a default alias for the container interface
//        $closure = static function(Container $container) {
//            return $container;
//        };
//        $this->define(Container::class)
//            ->factory(true)
//            ->using($closure)
//            ->build();
    }

    /**
     * @param Definition definition
     * @return $this
     */
    public function add(Definition $definition)
    {
        $this->definitions[$definition->getKey()] = $definition;
        return $this;
    }

    /**
     * @param string $interface
     * @param string $class
     * @return $this
     */
    public function alias($interface, $class)
    {
        $this->add(new Definition\AliasDefinition($interface, $class));
        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function blacklist($class)
    {
        $this->blacklist[$class] = true;
        return $this;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function className($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @param string|null $class
     * @return DefinitionBuilder
     */
    public function define($class = null)
    {
        return new DefinitionBuilder($this, $class);
    }

    /**
     * @param string $file
     * @return $this
     */
    public function file($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return Definition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param boolean $flag
     * @return $this
     */
    public function precompiled($flag = true)
    {
        $this->precompiled = (boolean) $flag;
        return $this;
    }

    /**
     * @param array $directories
     * @return $this
     */
    public function scanDirectories(array $directories)
    {
        foreach( $directories as $directory ) {
            $this->scanDirectory($directory);
        }
        return $this;
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function scanDirectory($directory)
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach( $it as $file ) {
            $path = $file->getPathName();
            if( substr($path, -4) !== '.php' ) {
                continue;
            }
            require_once $path;
        }
        return $this;
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function scanNamespace($namespace)
    {
        foreach( get_declared_classes() as $class ) {
            if( 0 !== strpos($class, $namespace) ) {
                continue;
            } else if( isset($this->blacklist[$class]) ) {
                continue;
            } else if( isset($this->definitions[$class]) ) {
                continue;
            }
            try {
                $this->define($class)->build();
            } catch ( \Exception $e ) {
                // @todo fixme
            }
        }

        return $this;
    }

    /**
     * @param boolean $flag
     * @return $this
     */
    public function stat($flag = true)
    {
        $this->stat = (boolean) $flag;
        return $this;
    }

    /**
     * @param integer $ttl
     * @return $this
     */
    public function ttl($ttl = 0)
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * @param array $namespaces
     * @return $this
     */
    public function scanNamespaces(array $namespaces)
    {
        foreach( $namespaces as $namespace ) {
            $this->scanNamespace($namespace);
        }
        return $this;
    }

    /**
     * @return Container
     */
    public function build()
    {
        if( $this->className ) {
            return $this->buildCompiled();
        } else {
            return $this->buildDefault();
        }
    }

    private function buildCompiled()
    {
        if( !$this->file ) {
            throw new Exception\DomainException('Must specify file');
        } else if( !$this->className ) {
            throw new Exception\DomainException('Must specify className');
        }

        if( $this->needsRebuild() ) {
            $compiler = new Compiler($this->definitions, $this->className);
            $code = $compiler->compile();
            file_put_contents($this->file, $code);
        }

        require_once $this->file;
        return new $this->className;
    }

    private function buildDefault()
    {
        return new RuntimeContainer(array(), $this->definitions);
    }

    public function needsRedefine()
    {
        if( $this->precompiled ) {
            // Precompiled never needs redefine
            return false;
        } else if( $this->className ) {
            // Compiled needs ttl check
            if( $this->ttl < 0 ) {
                return false;
            } else if( $this->ttl === 0 ) {
                return true;
            } else if( !file_exists($this->file) ) {
                return true;
            } else {
                clearstatcache(false, $this->file);
                return filemtime($this->file) + $this->ttl < time();
            }
        } else {
            // Runtime always needs redefine
            return true;
        }
    }

    public function needsRebuild()
    {
        if( $this->precompiled ) {
            // Precompiled never needs rebuild
            return false;
        } else if( $this->className ) {
            // Compiled need mtime check
            if( !file_exists($this->file) ) {
                return true;
            }

            if( !$this->stat ) {
                return false;
            }

            clearstatcache(false, $this->file);
            $mtime = filemtime($this->file);

            foreach( $this->definitions as $definition ) {
                $class = $definition->getClass();
                if( !class_exists($class, true) ) {
                    continue;
                }
                $reflectionClass = new \ReflectionClass($class);
                if( filemtime($reflectionClass->getFileName()) > $mtime ) {
                    return true;
                }
            }

            return false;
        } else {
            // Runtime always needs rebuild (it doesn't actually build)
            return true;
        }
    }
}
