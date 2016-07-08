<?php

namespace zdi\Container;

use zdi\Container;
use zdi\ContainerInterface;
use zdi\Dependency\AliasDependency;
use zdi\Dependency\Builder as DependencyBuilder;
use zdi\Dependency\AbstractDependency;

abstract class Builder
{
    /**
     * @var array
     */
    private $blacklist = array();

    /**
     * @var AbstractDependency[]
     */
    private $dependencies = array();

    /**
     * @param AbstractDependency $dependency
     * @return $this
     */
    public function add(AbstractDependency $dependency)
    {
        $this->dependencies[$dependency->getKey()] = $dependency;
        return $this;
    }

    /**
     * @param string $interface
     * @param string $class
     * @return $this
     */
    public function alias($interface, $class)
    {
        $this->add(new AliasDependency($interface, $class));
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
     * @return DependencyBuilder
     */
    public function define($class = null)
    {
        return new DependencyBuilder($this, $class);
    }

    /**
     * @return AbstractDependency[]
     */
    public function getDependencies()
    {
        return $this->dependencies;
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
            } else if( isset($this->dependencies[$class]) ) {
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
     * @return ContainerInterface
     */
    abstract public function build();

    /**
     * @return boolean
     */
    abstract public function isReady();
}
