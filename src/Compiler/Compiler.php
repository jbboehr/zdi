<?php

namespace zdi\Compiler;

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
     * @var array
     */
    private $uniques;

    /**
     * @var Definition[]
     */
    private $definitions;

    /**
     * Compiler constructor.
     * @param Definition[] $definitions
     * @param string $class
     * @param string $namespace
     * @param BuilderFactory|null $builderFactory
     */
    public function __construct(array $definitions, $className, BuilderFactory $builderFactory = null)
    {
        $this->definitions = $definitions;

        // Extract namespace/class
        $pos = strrpos($className, '\\');
        if( false === $pos ) {
            $this->class = $className;
        } else {
            $this->namespace = substr($className, 0, $pos);
            $this->class = substr($className, $pos + 1);
        }

        // Make builder factory
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
                $keyIdentifier = Utils::classToIdentifier($definition->getClass());
                $aliasIdentifier = Utils::classToIdentifier($definition->getAlias());
                $mapNodes[] = new Node\Expr\ArrayItem(
                    new Node\Scalar\String_($aliasIdentifier),
                    new Node\Scalar\String_($keyIdentifier)
                );
            } else {
                $identifier = $definition->getIdentifier();

                // Add method
                $class->addStmts($this->compileDefinition($definition));

                // Add map entry
                $mapNodes[] = new Node\Expr\ArrayItem(
                    new Node\Scalar\String_($identifier),
                    new Node\Scalar\String_($this->resolveUniqueIdentifier($definition->getKey()))
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
     * @param Definition $definition
     * @return \PhpParser\BuilderAbstract[]
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
            return new DefinitionCompiler\DataDefinitionCompiler($this->builderFactory, $definition, $this->definitions);
        } else if( $definition instanceof Definition\ClosureDefinition ) {
            return new DefinitionCompiler\ClosureDefinitionCompiler($this->builderFactory, $definition, $this->definitions);
        } else if( $definition instanceof Definition\ClassDefinition ) {
            return new DefinitionCompiler\ClassDefinitionCompiler($this->builderFactory, $definition, $this->definitions);
        } else {
            throw new Exception\DomainException('Unsupported definition: ' . get_class($definition));
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
