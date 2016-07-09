<?php

namespace zdi\Compiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\ContainerInterface;
use zdi\Dependency\DefaultDependency;
use zdi\Exception\DomainException;
use zdi\Param\ClassParam;
use zdi\Param\ParamInterface;
use zdi\Param\NamedParam;
use zdi\Param\ValueParam;
use zdi\Utils;

class DefaultDependencyCompiler implements DependencyCompilerInterface
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var DefaultDependency
     */
    private $dependency;

    /**
     * DefaultDependencyCompiler constructor.
     * @param BuilderFactory $builderFactory
     * @param DefaultDependency $dependency
     */
    public function __construct(BuilderFactory $builderFactory, DefaultDependency $dependency)
    {
        $this->builderFactory = $builderFactory;
        $this->dependency = $dependency;
    }

    /**
     * @inheritdoc
     */
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
        $property = null;
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
                new Node\Expr\Isset_(array($prop)),
                array('stmts' => array(new Node\Stmt\Return_($prop)))
            ));
        }

        // Prepare return variable
        if( $dependency->isFactory() ) {
            $retVar = new Node\Expr\Variable($identifier);
        } else {
            $retVar = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
        }

        // Compile constructor
        $paramNodes = array();
        foreach( $dependency->getParams() as $position => $param ) {
            $paramNodes[] = $this->compileParam($param);
        }
        $construct = new Node\Expr\New_(new Node\Name\FullyQualified($dependency->getClass()), $paramNodes);

        // Compile setters
        $setters = $this->compileSetters($dependency->getSetters(), $retVar);

        // Add return statement
        if( $setters ) {
            $method->addStmt(new Node\Expr\Assign(clone $retVar, $construct));
            $method->addStmts($setters);
        } else {
            $retVar = new Node\Expr\Assign(clone $retVar, $construct);
        }

        // Compile return
        $method->addStmt(new Node\Stmt\Return_($retVar));

        // Return statements
        return $property ? array($property, $method) : array($method);
    }

    /**
     * @param ParamInterface[] $setters
     * @param Node\Expr $var
     * @return Node\Stmt[]
     */
    private function compileSetters(array $setters, $var)
    {
        $stmts = array();
        foreach( $setters as $method => $param ) {
            $stmts[] = new Node\Expr\MethodCall(clone $var, $method, array(
                new Node\Arg($this->compileParam($param))
            ));
        }
        return $stmts;
    }

    /**
     * @param ParamInterface $param
     * @return Node\Expr
     * @throws DomainException
     */
    private function compileParam(ParamInterface $param)
    {
        if( $param instanceof ClassParam ) {
            $identifier = $param->getIdentifier();
            // Just return this if it's asking for a container
            if( $identifier === Utils::classToIdentifier(ContainerInterface::class) ) {
                return new Node\Expr\Variable('this');
            }
            $fetch = new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $identifier);
            if( $param->isOptional() ) {
                // @todo resolve this at compile-time
                return new Node\Expr\Ternary(
                    new Node\Expr\FuncCall(new Node\Name('method_exists'), array(
                        new Node\Arg(new Node\Expr\Variable('this')),
                        new Node\Arg(new Node\Scalar\String_($identifier))
                    )),
                    $fetch,
                    new Node\Expr\ConstFetch(new Node\Name('null'))
                );
            }
            return $fetch;
        } else if( $param instanceof NamedParam ) {
            return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'get', array(
                new Node\Arg(new Node\Scalar\String_($param->getName()))
            ));
        } else if( $param instanceof ValueParam ) {
            return new Node\Arg($this->compileValue($param->getValue()));
        } else {
            throw new DomainException('Unsupported parameter: ' . Utils::varInfo($param));
        }
    }

    /**
     * @param mixed $value
     * @return Node\Expr
     * @throws DomainException
     */
    private function compileValue($value)
    {
        return Utils::parserNodeFromValue($value);
    }
}
