<?php

namespace zdi\Dependency;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;

use zdi\Container;

use zdi\Param\ParamInterface;
use zdi\Param\ClassParam;
use zdi\Param\NamedParam;
use zdi\Param\ValueParam;

class Builder
{
    /**
     * @var Container
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
     * @param Container $container
     * @param string|null $class
     */
    public function __construct(Container $container, $class = null)
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
     * @param string|ParamInterface $containerKey
     * @return $this
     */
    public function param($paramName, $containerKey)
    {
        $this->params[$paramName] = $containerKey;
        return $this;
    }

    /**
     * @param $method
     * @param string|ParamInterface|null $containerKey
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
     * @return AbstractDependency
     * @throws Exception
     */
    public function build()
    {
        if( is_string($this->provider) ) {
            $dependency = new ProviderDependency($this->class, $this->factory, $this->name, $this->provider);
        } else if( $this->provider instanceof Closure ) {
            $dependency = new ClosureDependency($this->class, $this->factory, $this->name, $this->provider);
        } else if( $this->class ) {
            $reflectionClass = new ReflectionClass($this->class);
            $params = $this->convertParameters($reflectionClass->getConstructor());
            $setters = $this->convertSetters($reflectionClass);
            $dependency = new DefaultDependency($this->class, $this->factory, $this->name, $params, $setters);
        } else {
            throw new Exception('Unable to determine dependency type');
        }

        // Add to container
        $this->container->add($dependency);
        if( $this->alias ) {
            $this->container->alias($this->alias, $this->class);
        }

        return $dependency;
    }

    /**
     * @param ReflectionMethod|null $reflectionMethod
     * @return ParamInterface[]
     * @throws Exception
     */
    private function convertParameters(ReflectionMethod $reflectionMethod = null)
    {
        $result = array();
        if( !$reflectionMethod ) {
            return $result;
        }
        foreach( $reflectionMethod->getParameters() as $parameter ) {
            $position = $parameter->getPosition();
            $name = $parameter->getName();
            $class = $parameter->getClass();
            if( isset($this->params[$name]) ) {
                $result[$position] = $this->convertParam($this->params[$name]);
            } else if( isset($this->params[$position]) ) {
                $result[$position] = $this->convertParam($this->params[$position]);
            } else if( $class ) {
                $result[$position] = new ClassParam($class->name);
            } else if( $parameter->isDefaultValueAvailable() ) {
                $result[$position] = new ValueParam($parameter->getDefaultValue());
            } else {
                throw new Exception('Unresolved paramter: ' . $name . ' for ' . $this->class ?: $this->name);
            }
        }
        return $result;
    }

    /**
     * @param $param
     * @return ParamInterface
     * @throws Exception
     */
    private function convertParam($param)
    {
        if( is_string($param) ) {
            return new NamedParam($param);
        } else if( $param instanceof ParamInterface ) {
            return $param;
        } else if( $param instanceof ReflectionClass ) {
            return new ClassParam($param->name);
        } else {
            throw new Exception("Invalid param");
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return ParamInterface[]
     * @throws Exception
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
     * @return ParamInterface[]
     * @throws Exception
     */
    private function convertSetter(ReflectionMethod $reflectionMethod, $param)
    {
        $parameters = $reflectionMethod->getParameters();
        $parameter = $parameters[0];
        $class = $parameter->getClass();
        if( $param !== null ) {
            return $this->convertParam($param);
        } else if( $class !== null ) {
            return new ClassParam($class->name);
        } else {
            throw new Exception('Unknown setter value');
        }
    }
}
