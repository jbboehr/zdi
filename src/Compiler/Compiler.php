<?php

namespace zdi\Compiler;

use PhpParser\BuilderFactory;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;

use zdi\Container;
use zdi\Dependency\AliasDependency;
use zdi\Dependency\ProviderDependency;
use zdi\Exception;
use zdi\Utils;

use zdi\Dependency\AbstractDependency;
use zdi\Dependency\ClosureDependency;
use zdi\Dependency\DefaultDependency;

class Compiler
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $uniques;

    /**
     * @var AbstractDependency[]
     */
    private $dependencies;

    /**
     * Compiler constructor.
     * @param AbstractDependency[] $dependencies
     * @param string $class
     * @param string $namespace
     * @param BuilderFactory|null $builderFactory
     */
    public function __construct(array $dependencies, $namespace, $class, BuilderFactory $builderFactory = null)
    {
        $this->dependencies = $dependencies;
        $this->namespace = $namespace;
        $this->class = $class;

        if( !$builderFactory ) {
            $builderFactory = new BuilderFactory();
        }
        $this->builderFactory = $builderFactory;
    }

    /**
     * @return string
     * @throws Exception\DomainException
     */
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
     * @throws Exception\DomainException
     */
    private function compileClass()
    {
        $dependencies = $this->dependencies;

        $class = $this->builderFactory->class($this->class)
            ->extend('CompiledContainer');

        $mapNodes = array();

        foreach( $dependencies as $dependency ) {
            if( $dependency instanceof AliasDependency ) {
                $key = $dependency->getClass();
                $alias = $dependency->getAlias();
                $keyIdentifier = Utils::classToIdentifier($dependency->getClass());
                $aliasIdentifier = Utils::classToIdentifier($dependency->getAlias());
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
            } else {
                $identifier = $dependency->getIdentifier();

                // Add method
                $class->addStmts($this->compileDependency($dependency));

                // Add map entry
                $mapNodes[] = new Node\Expr\ArrayItem(
                    new Node\Scalar\String_($identifier),
                    new Node\Scalar\String_($this->resolveUniqueIdentifier($dependency->getKey()))
                );

                $this->uniques[strtolower($identifier)] = $identifier;
            }
        }

        $class->addStmt(
            $this->builderFactory->property('map')
                ->makeProtected()
                ->makeStatic()
                ->setDefault(new Node\Expr\Array_($mapNodes))
                ->setDocComment('/**
                                  * @var array
                                  */')
        );

        return $class;
    }

    /**
     * @param AbstractDependency $dependency
     * @return \PhpParser\BuilderAbstract[]
     * @throws Exception\DomainException
     */
    private function compileDependency(AbstractDependency $dependency)
    {
        return $this->makeDependencyCompiler($dependency)->compile();
    }

    /**
     * @param AbstractDependency $dependency
     * @return DependencyCompilerInterface
     * @throws Exception\DomainException
     */
    private function makeDependencyCompiler(AbstractDependency $dependency)
    {
        if( $dependency instanceof DefaultDependency ) {
            return new DefaultDependencyCompiler($this->builderFactory, $dependency);
        } else if( $dependency instanceof ClosureDependency ) {
            return new ClosureDependencyCompiler($this->builderFactory, $dependency);
        } else if( $dependency instanceof ProviderDependency ) {
            return new ProviderDependencyCompiler($this->builderFactory, $dependency);
        } else {
            throw new Exception\DomainException('Unsupported dependency: ' . get_class($dependency));
        }
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
