<?php

namespace zdi;

use Closure;
use Exception;

use zdi\Dependency\Builder as DependencyBuilder;
use zdi\Dependency\AbstractDependency;
use zdi\Dependency\ClosureDependency;
use zdi\Dependency\DefaultDependency;
use zdi\Dependency\ProviderDependency;

use zdi\Param\ParamInterface;
use zdi\Param\NamedParam;
use zdi\Param\ClassParam;
use zdi\Param\ValueParam;

class Container implements ContainerInterface
{
    /**
     * @var string[]
     */
    private $aliases = array();

    /**
     * @var AbstractDependency[]
     */
    private $dependencies = array();

    /**
     * @var array
     */
    private $values = array();

    /**
     * Container constructor.
     * @param array $values
     */
    public function __construct($values = array())
    {
        $this->values = $values;
    }

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
        $this->aliases[$interface] = $class;
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
     * @inheritdoc
     */
    public function get($key)
    {
        // Already made
        if( isset($this->values[$key]) ) {
            return $this->values[$key];
        }

        // Use an alias
        if( isset($this->aliases[$key]) ) {
            return $this->get($this->aliases[$key]);
        }

        // Create the dependency if not available
        if( isset($this->dependencies[$key]) ) {
            $dependency = $this->dependencies[$key];
        } else if( is_a($this, $key) ) {
            return $this;
        } else if( class_exists($key, true) ) {
            $dependency = $this->define($key)->build();
        } else {
            throw new Exception("Not defined");
        }

        // Build the parameters
        if( $dependency instanceof DefaultDependency ) {
            $object = $this->makeDefault($dependency);
        } else if( $dependency instanceof ClosureDependency ) {
            $object = $this->makeClosure($dependency);
        } else if( $dependency instanceof ProviderDependency ) {
            $object = $this->makeProvider($dependency);
        } else {
            throw new Exception('unknown dependency type');
        }

        if( !$dependency->isFactory() ) {
            $this->values[$key] = $object;
        }

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return isset($this->values[$key])
            || isset($this->aliases[$key])
            || isset($this->dependencies[$key]);
    }

    /**
     * @return AbstractDependency[]
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if( $value instanceof AbstractDependency ) {
            $this->dependencies[$offset] = $value;
        } else if( $value instanceof \Closure ) {
            $this->define()
                ->name($offset)
                ->using($value)
                ->build();
        } else {
            $this->values[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->aliases[$offset]);
        unset($this->dependencies[$offset]);
        unset($this->values[$offset]);
    }

    private function makeDefault(DefaultDependency $dependency)
    {
        $class = $dependency->getClass();

        $params = array();
        foreach( $dependency->getParams() as $position => $param ) {
            $params[$position] = $this->makeParam($param);
        }

        // Build the class
        $reflectionClass = new \ReflectionClass($class);
        $object = $reflectionClass->newInstanceArgs($params);

        // Do setters
        foreach( $dependency->getSetters() as $name => $param ) {
            $object->{$name}($this->makeParam($param));
        }

        return $object;
    }

    private function makeClosure(ClosureDependency $dependency)
    {
        $closure = $dependency->getClosure();
        return $closure($this);
    }

    private function makeProvider(ProviderDependency $dependency)
    {
        $provider = $this->get($dependency->getProvider());
        return $provider->get();
    }

    private function makeParam(ParamInterface $param)
    {
        if( $param instanceof NamedParam ) {
            return $this->get($param->getName());
        } else if( $param instanceof ClassParam ) {
            return $this->get($param->getClass());
        } else if( $param instanceof ValueParam ) {
            return $param->getValue();
        } else {
            throw new Exception("Invalid param");
        }
    }
}
