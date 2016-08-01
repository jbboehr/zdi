<?php

namespace zdi\Container;

use zdi\Container;
use zdi\Compiler\Compiler;
use zdi\Definition;
use zdi\Definition\DefinitionBuilder;
use zdi\Exception;
use zdi\Module;
use zdi\Utils;

/**
 * Class ContainerBuilder
 * @package zdi\Container
 * @property-read boolean $precompiled
 */
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
     * @var string[]
     */
    private $directories = array();

    /**
     * @var string
     */
    private $file;

    /**
     * @var string[]
     */
    private $interfaces;

    /**
     * @var array
     */
    private $modules = array();

    /**
     * @var string[]
     */
    private $namespaces = array();

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
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if( property_exists($this, $name) ) {
            return $this->$name;
        } else {
            return null;
        }
    }

    /**
     * @param Definition $definition
     * @return $this
     */
    public function addDefinition(Definition $definition)
    {
        $this->definitions[$definition->getKey()] = $definition;
        return $this;
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function addDirectory($directory)
    {
        $this->directories[] = $directory;
        return $this;
    }

    /**
     * @param string[] $directories
     * @return $this
     */
    public function addDirectories(array $directories)
    {
        foreach( $directories as $directory ) {
            $this->directories[] = $directory;
        }
        return $this;
    }

    /**
     * @param string $interface
     * @return $this
     */
    public function addInterface($interface)
    {
        $this->addDefinition(Definition\InterfaceDefinition::fromString($interface));
        return $this;
    }

    /**
     * @param Module|string $module
     * @return $this
     */
    public function addModule($module)
    {
        $this->modules[] = $module;
        return $this;
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function addNamespace($namespace)
    {
        $this->namespaces[] = $namespace;
        return $this;
    }

    /**
     * @param string[] $namespaces
     * @return $this
     */
    public function addNamespaces(array $namespaces)
    {
        foreach( $namespaces as $namespace ) {
            $this->namespaces[] = $namespace;
        }
        return $this;
    }

    /**
     * @param string $interface
     * @param string $class
     * @return $this
     */
    public function alias($interface, $class)
    {
        $this->addDefinition(new Definition\AliasDefinition($interface, $class));
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
     * @return Container
     */
    public function build()
    {
        if( $this->needsRedefine() ) {
            $this->scanDirectories();
            $this->executeModules();
            $this->scanNamespaces();
        }

        if( $this->className || $this->file ) {
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

            if( !$this->isWritable() || !file_put_contents($this->file, $code) ) {
                throw new Exception\IOException('Failed to write ' . $this->file);
            }
        }

        include_once $this->file;

        if( !class_exists($this->className, false) ) {
            throw new Exception\ClassNotFoundException('Class "' . $this->className . '" not found"');
        }

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
                return true;
            }

            clearstatcache(false, $this->file);
            $mtime = filemtime($this->file);

            foreach( $this->definitions as $definition ) {
                $class = $definition->getClass();
                if( class_exists($class, true) ) {
                    $reflectionClass = new \ReflectionClass($class);
                    if( filemtime($reflectionClass->getFileName()) > $mtime ) {
                        return true;
                    }
                }
            }

            return false;
        } else {
            // Runtime always needs rebuild (it doesn't actually build)
            return true;
        }
    }

    private function executeModules()
    {
        foreach( $this->modules as $module ) {
            if( is_string($module) ) {
                $module = new $module();
            }
            if( !($module instanceof Module) ) {
                throw new Exception\DomainException('Module must implement zdi\\Module, was: ' . Utils::varInfo($module));
            }
            $module->define($this);
        }
    }

    private function scanDirectories()
    {
        foreach( $this->directories as $directory ) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
            foreach( $it as $file ) {
                $path = $file->getPathName();
                if( substr($path, -4) !== '.php') {
                    continue;
                }
                require_once $path;
            }
        }
        return $this;
    }

    private function scanNamespaces()
    {
        foreach( get_declared_classes() as $class ) {
            $match = false;
            foreach( $this->namespaces as $namespace ) {
                if( isset($this->blacklist[$class]) || isset($this->definitions[$class]) ) {
                    continue 2;
                }
                if( strpos($class, $namespace) === 0 ) {
                    $match = true;
                    break;
                }
            }
            if( $match ) {
                try {
                    $this->define($class)->build();
                } catch ( \zdi\Exception $e ) {
                    // @todo fixme
                }
            }
        }

        return $this;
    }

    private function isWritable()
    {
        if( file_exists($this->file) ) {
            return is_writable($this->file);
        } else {
            return is_writable(dirname($this->file));
        }
    }
}
