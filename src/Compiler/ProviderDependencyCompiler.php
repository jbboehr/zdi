<?php

namespace zdi\Compiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\ContainerInterface;
use zdi\Dependency\ProviderDependency;
use zdi\Exception;
use zdi\Utils;

class ProviderDependencyCompiler implements DependencyCompilerInterface
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var ProviderDependency
     */
    private $dependency;

    /**
     * ClosureDependencyCompiler constructor.
     * @param BuilderFactory $builderFactory
     * @param ProviderDependency $dependency
     */
    public function __construct(BuilderFactory $builderFactory, ProviderDependency $dependency)
    {
        $this->builderFactory = $builderFactory;
        $this->dependency = $dependency;
    }

    public function compile()
    {
        $dependency = $this->dependency;
        $identifier = $dependency->getIdentifier();

        // Prepare method
        $method = $this->builderFactory->method($identifier)
            ->makeProtected()
            ->setDocComment('/**
                              * @return ' . $dependency->getTypeHint() . '
                              */');

        // Prepare instance check
        $property = $prop = null;
        if( !$dependency->isFactory() ) {
            // Add property to store instance
            $property = $this->builderFactory->property($identifier)
                ->makePrivate()
                ->setDocComment('/**
                               * @var ' . $dependency->getTypeHint() . '
                               */');

            // Add instance check
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
            $method->addStmt(new Node\Stmt\If_(
                new Node\Expr\Isset_(array(clone $prop)),
                array('stmts' => array(new Node\Stmt\Return_($prop)))
            ));
        }

        // Prepare method body
        $providerIdentifier = Utils::classToIdentifier($dependency->getProvider());
        $fetch = new Node\Expr\MethodCall(new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $providerIdentifier), 'get');

        if( $prop ) {
            $method->addStmt(new Node\Stmt\Return_(new Node\Expr\Assign(clone $prop, $fetch)));
        } else {
            $method->addStmt(new Node\Stmt\Return_($fetch));
        }

        return $property ? array($property, $method) : array($method);
    }
}
