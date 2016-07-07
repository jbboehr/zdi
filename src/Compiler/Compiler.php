<?php

namespace zdi\Compiler;

use PhpParser\BuilderFactory;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;

use zdi\AbstractContainer;
use zdi\Container;
use zdi\Utils;

use zdi\ContainerInterface;
use zdi\Dependency\AbstractDependency;
use zdi\Dependency\ClosureDependency;
use zdi\Dependency\Dependency;

use zdi\Param\ClassParam;
use zdi\Param\ParamInterface;
use zdi\Param\LookupParam;
use zdi\Param\NamedParam;
use zdi\Param\ValueParam;

class Compiler
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    private $namespace;

    private $class;

    private $uniques;

    /**
     * @var Container
     */
    private $container;

    private $blacklist = array();

    public function __construct(Container $container, $namespace, $class, BuilderFactory $builderFactory = null)
    {
        $this->container = $container;
        $this->namespace = $namespace;
        $this->class = $class;

        if( !$builderFactory ) {
            $builderFactory = new BuilderFactory();
        }
        $this->builderFactory = $builderFactory;
    }

    public function blacklist($class)
    {
        $this->blacklist[$class] = true;
        return $this;
    }

    public function scanDir($directory)
    {
        //$before = get_declared_classes();

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach( $it as $file ) {
            $path = $file->getPathName();
            if( substr($path, -4) !== '.php' ) {
                continue;
            }
            require_once $path;
        }

        /**
        $after = get_declared_classes();
        $diff = array_diff($after, $before);

        foreach( $diff as $class ) {
            if( !isset($this->blacklist[$class]) && !$this->container->has($class) ) {
                $this->container->define($class)->build();
            }
        }
         **/
    }

    public function scanNamespace($namespace)
    {
        foreach( get_declared_classes() as $class ) {
            if( 0 === strpos($class, $namespace) ) {
                if( !isset($this->blacklist[$class]) && !$this->container->has($class) ) {
                    try {
                        $this->container->define($class)->build();
                    } catch( \Exception $e ) {
                        // @todo fixme
                    }
                }
            }
        }
    }

    public function compile()
    {
        $class = $this->compileClass();
        $node = $this->builderFactory->namespace($this->namespace)
            ->addStmt($this->builderFactory->use('zdi\\ContainerInterface'))
            ->addStmt($this->builderFactory->use('zdi\\CompiledContainer'))
            ->addStmt($class)
            ->getNode();
        $prettyPrinter = new PrettyPrinter\Standard();
        return $prettyPrinter->prettyPrintFile(array($node));
    }


    /**
     * @return Builder\Class_
     * @throws \Exception
     */
    private function compileClass()
    {
        $container = $this->container;
        $dependencies = $container->getDependencies();
        $aliases = $container->getAliases();

        $class = $this->builderFactory->class($this->class)
            ->extend('CompiledContainer');

        $mapNodes = array();

        foreach( $dependencies as $dependency ) {
            $identifier = $dependency->getIdentifier();

            // Add method
            $method = $this->compileDependency($dependency);
            $class->addStmt($method);

            // Add property for method
            $property = $this->builderFactory->property($identifier)
                ->makePrivate()
                ->setDocComment('/**
                                  * @var ' . $dependency->getClass() . '
                                  */');
            $class->addStmt($property);

            // Add map entry
            //if( $dependency->getClass() ) {
                $mapNodes[] = new Node\Expr\ArrayItem(
                    new Node\Scalar\String_($identifier),
                    new Node\Scalar\String_($this->resolveUniqueIdentifier($dependency->getKey()))
                );
            //}

            $this->uniques[strtolower($identifier)] = $identifier;
        }

        // Add aliases (@todo)
        foreach( $aliases as $key => $alias ) {
            $keyIdentifier = Utils::classToIdentifier($key);
            $aliasIdentifier = Utils::classToIdentifier($alias);
            $mapNodes[] = new Node\Expr\ArrayItem(
                new Node\Scalar\String_(Utils::classToIdentifier($alias)),
                new Node\Scalar\String_($keyIdentifier)
            );
            $mapNodes[] = new Node\Expr\ArrayItem(
                new Node\Scalar\String_($keyIdentifier),
                new Node\Scalar\String_($key)
            );
            if( isset($dependencies[$alias]) ) {
                if( !isset($this->uniques[strtolower($keyIdentifier)]) ) {
                    $method = $this->builderFactory->method($keyIdentifier)
                        ->makeProtected();
                    $method->addStmt(new Node\Stmt\Return_(new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $aliasIdentifier)));
                    $class->addStmt($method);
                }
            }
        }

        $class->addStmt($this->builderFactory->property('map')
            ->makeProtected()
            ->makeStatic()
            ->setDefault(new Node\Expr\Array_($mapNodes)));

        return $class;
    }

    /**
     * @param AbstractDependency $dependency
     * @return Builder\Method
     * @throws \Exception
     */
    private function compileDependency(AbstractDependency $dependency)
    {
        if( $dependency instanceof Dependency ) {
            $compiler = new DefaultDependencyCompiler($this->builderFactory, $dependency);
            //return $this->compileDefault($dependency);
        } else if( $dependency instanceof ClosureDependency ) {
            //return $this->compileClosure($dependency);
            $compiler = new ClosureDependencyCompiler($this->builderFactory, $dependency);
        } else {
            throw new \Exception('Unsupported dependency type');
        }

        return $compiler->compile();
    }

    private function resolveUniqueIdentifier($key)
    {
        $lower = $key;
        if( isset($this->uniques[$lower]) ) {
            return $this->uniques[$lower];
        } else {
            $this->uniques[$lower] = $key;
            return $key;
        }
    }
}
