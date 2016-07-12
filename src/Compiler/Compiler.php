<?php

namespace zdi\Compiler;

use ArrayObject;

use PhpParser\BuilderFactory;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;

use zdi\Container;
use zdi\Definition;
use zdi\Exception;
use zdi\Utils;

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
     * @var Definition[]
     */
    private $definitions;

    /**
     * @var ArrayObject
     */
    private $astCache;

    /**
     * Compiler constructor.
     * @param Definition[] $definitions
     * @param string $className
     * @param BuilderFactory|null $builderFactory
     */
    public function __construct(array $definitions, $className, BuilderFactory $builderFactory = null)
    {
        $this->definitions = $definitions;
        list($this->namespace, $this->class) = Utils::extractNamespace($className);
        $this->builderFactory = $builderFactory ?: new BuilderFactory();
        $this->astCache = new ArrayObject();
    }

    /**
     * @return string
     * @throws Exception\DomainException
     */
    public function compile()
    {
        $class = $this->compileClass();
        $node = $this->builderFactory->namespace($this->namespace)
            ->addStmt($this->builderFactory->use('zdi\\Container'))
            ->addStmt($this->builderFactory->use('zdi\\Container\CompiledContainer'))
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
        $definitions = $this->definitions;

        $class = $this->builderFactory->class($this->class)
            ->extend('CompiledContainer');

        $mapNodes = array();

        foreach( $definitions as $definition ) {
            if( $definition instanceof Definition\AliasDefinition ) {
                $alias = Utils::resolveAlias($this->definitions, $definition, false);
                $mapNodes[] = new Node\Expr\ArrayItem(
                    new Node\Scalar\String_($alias->getIdentifier()),
                    new Node\Scalar\String_($definition->getIdentifier())
                );
            } else {
                if( !$definition->isFactory() ) {
                    $class->addStmt(
                        $property = $this->builderFactory->property($definition->getIdentifier())
                            ->makePrivate()
                            ->setDocComment('/**
                                              * @var ' . $definition->getTypeHint() . '
                                              */')
                    );
                }

                $identifier = $definition->getIdentifier();

                // Add method
                $class->addStmt($this->compileDefinition($definition));

                // Add map entry
                $mapNodes[] = new Node\Expr\ArrayItem(
                    new Node\Scalar\String_($identifier),
                    new Node\Scalar\String_($definition->getKey())
                );
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
     * @param Definition $definition
     * @return \PhpParser\BuilderAbstract
     * @throws Exception\DomainException
     */
    private function compileDefinition(Definition $definition)
    {
        return $this->makeDefinitionCompiler($definition)->compile();
    }

    /**
     * @param Definition $definition
     * @return DefinitionCompiler
     * @throws Exception\DomainException
     */
    private function makeDefinitionCompiler(Definition $definition)
    {
        if( $definition instanceof Definition\DataDefinition ) {
            return new DefinitionCompiler\DataDefinitionCompiler($this->builderFactory, $definition, $this->definitions, $this->astCache);
        } else if( $definition instanceof Definition\ClosureDefinition ) {
            return new DefinitionCompiler\ClosureDefinitionCompiler($this->builderFactory, $definition, $this->definitions, $this->astCache);
        } else if( $definition instanceof Definition\ClassDefinition ) {
            return new DefinitionCompiler\ClassDefinitionCompiler($this->builderFactory, $definition, $this->definitions, $this->astCache);
        } else {
            throw new Exception\DomainException('Unsupported definition: ' . get_class($definition));
        }
    }
}
