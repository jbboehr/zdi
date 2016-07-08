<?php

namespace zdi\Compiler;

use Exception;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\Dependency\DefaultDependency;

use zdi\Param\ClassParam;
use zdi\Param\ParamInterface;
use zdi\Param\NamedParam;
use zdi\Param\ValueParam;

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

        $method = $this->builderFactory->method($identifier)
            ->makeProtected()
            ->setDocComment('/**
                              * @return ' . $dependency->getTypeHint() . '
                              */');

        $paramNodes = array();
        foreach( $dependency->getParams() as $position => $param ) {
            $paramNodes[] = $this->compileParam($param);
        }

        $construct = new Node\Expr\New_(new Node\Name\FullyQualified($dependency->getClass()), $paramNodes);

        if( $dependency->isFactory() ) {
            $ret = new Node\Stmt\Return_($construct);
            $method->addStmt($ret);
            //return $method;
        } else {
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
            $retProp = new Node\Stmt\Return_($prop);
            $method->addStmt(new Node\Stmt\If_(
                new Node\Expr\BooleanNot(new Node\Expr\Isset_(array($prop))),
                array('stmts' => array(new Node\Expr\Assign($prop, $construct)))
            ));
            $method->addStmt($retProp);
            //return $method;
        }

        $property = $this->builderFactory->property($identifier)
            ->makePrivate()
            ->setDocComment('/**
                               * @var ' . $dependency->getClass() . '
                               */');

        return array($property, $method);
    }

    /**
     * @param ParamInterface $param
     * @return Node
     * @throws Exception
     */
    private function compileParam(ParamInterface $param)
    {
        if( $param instanceof ClassParam ) {
            $identifier = $param->getIdentifier();
            return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $identifier);
        } else if( $param instanceof NamedParam ) {
            return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'get', array(
                new Node\Arg(new Node\Scalar\String_($param->getName()))
            ));
        } else if( $param instanceof ValueParam ) {
            return new Node\Arg($this->compileValue($param->getValue()));
        } else {
            throw new Exception('Unknown param type');
        }
    }

    /**
     * @param mixed $value
     * @return Node\Expr
     * @throws Exception
     */
    private function compileValue($value)
    {
        if( is_array($value) ) {
            $items = array();
            foreach( $value as $k => $v ) {
                $items[] = new Node\Expr\ArrayItem($this->compileValue($v), $this->compileValue($k));
            }
            return new Node\Expr\Array_($items);
        } else if( is_string($value) ) {
            return new Node\Scalar\String_($value);
        } else if( is_int($value) ) {
            return new Node\Scalar\LNumber($value);
        } else if( is_float($value) ) {
            return new Node\Scalar\DNumber($value);
        } else if( is_null($value) ) {
            return new Node\Expr\ConstFetch(new Node\Name('null'));
        } else if( is_bool($value) ) {
            return new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false'));
        } else {
            throw new Exception('Unknown value type: ' . gettype($value));
        }
    }
}
