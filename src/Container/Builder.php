<?php

namespace zdi\Container;

use zdi\Container;
use zdi\Definition;
use zdi\Definition\DefinitionBuilder;

abstract class Builder
{
    /**
     * @var array
     */
    private $blacklist = array();

    /**
     * @var Definition[]
     */
    private $definitions = array();

    /**
     * Builder constructor.
     */
    public function __construct()
    {
        // Add a default alias for the container interface
        $closure = static function(Container $container) {
            return $container;
        };
        $this->add(new Definition\ClosureDefinition(Container::class, true, null, $closure));
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
     * @param string|null $class
     * @return DefinitionBuilder
     */
    public function define($class = null)
    {
        return new DefinitionBuilder($this, $class);
    }

    /**
     * @return Definition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
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
    abstract public function build();

    /**
     * @return boolean
     */
    abstract public function isReady();
}
