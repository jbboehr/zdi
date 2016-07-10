<?php

namespace zdi\Definition;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

use zdi\Container;
use zdi\Container\ContainerBuilder;
use zdi\Definition;
use zdi\Exception;
use zdi\Param;

class DefinitionBuilder
{
    /**
     * @var ContainerBuilder|null
     */
    private $container;

    /**
     * @var string
     */
    private $class;

    /**
     * @var boolean
     */
    private $factory = false;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $params = array();

    /**
     * @var array
     */
    private $setters = array();

    /**
     * @var Closure
     */
    private $provider;

    /**
     * Builder constructor.
     * @param ContainerBuilder|null $container
     * @param string|null $class
     */
    public function __construct(ContainerBuilder $container = null, $class = null)
    {
        $this->container = $container;
        $this->class = $class;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return $this
     */
    public function factory()
    {
        $this->factory = true;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $paramName
     * @param string|Param $containerKey
     * @return $this
     */
    public function param($paramName, $containerKey)
    {
        $this->params[$paramName] = $containerKey;
        return $this;
    }

    /**
     * @param $method
     * @param string|Param|null $containerKey
     * @return $this
     */
    public function setter($method, $containerKey = null)
    {
        $this->setters[$method] = $containerKey;
        return $this;
    }

    /**
     * @return $this
     */
    public function singleton()
    {
        $this->factory = false;
        return $this;
    }

    /**
     * @param Closure $provider
     * @return $this
     */
    public function using($provider)
    {
        $this->params = null;
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return Definition
     * @throws Exception\DomainException
     */
    public function build()
    {
        if( is_string($this->provider) ) {
            $definition = new ClassDefinition($this->class, $this->factory, $this->name, $this->provider);
        } else if( $this->provider instanceof Closure ) {
            $closure = $this->provider;
            $reflectionFunction = new ReflectionFunction($closure);
            $params = $this->convertParameters($reflectionFunction->getParameters());
            $definition = new ClosureDefinition($this->class, $this->factory, $this->name, $closure, $params);
        } else if( $this->class ) {
            $reflectionClass = new ReflectionClass($this->class);
            $params = $this->convertConstructor($reflectionClass);
            $setters = $this->convertSetters($reflectionClass);
            $definition = new DataDefinition($this->class, $this->factory, $this->name, $params, $setters);
        } else {
            throw new Exception\DomainException('Unable to determine definition type');
        }

        // Add to container
        if( null !== $this->container ) {
            $this->container->add($definition);
            if( $this->alias ) {
                $this->container->alias($this->alias, $this->class);
            }
        }

        return $definition;
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return Param[]
     * @throws Exception\DomainException
     */
    private function convertConstructor(ReflectionClass $reflectionClass)
    {
        $reflectionMethod = $reflectionClass->getConstructor();
        if( $reflectionMethod ) {
            return $this->convertParameters($reflectionMethod->getParameters());
        } else {
            return array();
        }
    }

    /**
     * @param ReflectionParameter[] $parameters
     * @return Param[]
     * @throws Exception\DomainException
     */
    private function convertParameters($parameters)
    {
        $result = array();
        foreach( $parameters as $parameter ) {
            $position = $parameter->getPosition();
            $name = $parameter->getName();
            $class = $parameter->getClass();
            if( isset($this->params[$name]) ) {
                $result[$position] = $this->convertParam($this->params[$name], $parameter->isOptional());
            } else if( isset($this->params[$position]) ) {
                $result[$position] = $this->convertParam($this->params[$position], $parameter->isOptional());
            } else if( $class ) {
                $result[$position] = new Param\ClassParam($class->name, $parameter->isOptional());
            } else if( $parameter->isDefaultValueAvailable() ) {
                $result[$position] = new Param\ValueParam($parameter->getDefaultValue());
            } else {
                throw new Exception\DomainException(
                    'Unresolved parameter: "' . $name . '" for ' . $this->class ?: $this->name
                );
            }
        }
        return $result;
    }

    /**
     * @param $param
     * @return Param
     * @throws Exception\DomainException
     */
    private function convertParam($param, $isOptional = false)
    {
        if( is_string($param) ) {
            return new Param\NamedParam($param);
        } else if( $param instanceof Param ) {
            return $param;
        } else {
            throw new Exception\DomainException("Invalid param");
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return Param[]
     * @throws Exception\DomainException
     */
    private function convertSetters(ReflectionClass $reflectionClass)
    {
        $setters = array();
        foreach( $this->setters as $name => $param ) {
            $reflectionMethod = $reflectionClass->getMethod($name);
            $setters[$name] = $this->convertSetter($reflectionMethod, $param);
        }
        return $setters;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @param $param
     * @return Param[]
     * @throws Exception\DomainException
     */
    private function convertSetter(ReflectionMethod $reflectionMethod, $param)
    {
        $parameters = $reflectionMethod->getParameters();
        $parameter = $parameters[0];
        $class = $parameter->getClass();
        if( $param !== null ) {
            return $this->convertParam($param);
        } else if( $class !== null ) {
            return new Param\ClassParam($class->name);
        } else {
            throw new Exception\DomainException('Unknown setter value');
        }
    }
}