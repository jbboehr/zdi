<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\Container;
use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Definition\ClassDefinition;
use zdi\Exception;
use zdi\Utils;

class ClassDefinitionCompiler implements DefinitionCompiler
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var ClassDefinition
     */
    private $definition;

    /**
     * @param BuilderFactory $builderFactory
     * @param ClassDefinition $definition
     */
    public function __construct(BuilderFactory $builderFactory, ClassDefinition $definition)
    {
        $this->builderFactory = $builderFactory;
        $this->definition = $definition;
    }

    public function compile()
    {
        $definition = $this->definition;
        $identifier = $definition->getIdentifier();

        // Prepare method
        $method = $this->builderFactory->method($identifier)
            ->makeProtected()
            ->setDocComment('/**
                              * @return ' . $definition->getTypeHint() . '
                              */');

        // Prepare instance check
        $property = $prop = null;
        if( !$definition->isFactory() ) {
            // Add property to store instance
            $property = $this->builderFactory->property($identifier)
                ->makePrivate()
                ->setDocComment('/**
                               * @var ' . $definition->getTypeHint() . '
                               */');

            // Add instance check
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
            $method->addStmt(new Node\Stmt\If_(
                new Node\Expr\Isset_(array(clone $prop)),
                array('stmts' => array(new Node\Stmt\Return_($prop)))
            ));
        }

        // Prepare method body
        $providerIdentifier = Utils::classToIdentifier($definition->getProvider());
        $fetch = new Node\Expr\MethodCall(new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $providerIdentifier), 'get');

        if( $prop ) {
            $method->addStmt(new Node\Stmt\Return_(new Node\Expr\Assign(clone $prop, $fetch)));
        } else {
            $method->addStmt(new Node\Stmt\Return_($fetch));
        }

        return $property ? array($property, $method) : array($method);
    }
}