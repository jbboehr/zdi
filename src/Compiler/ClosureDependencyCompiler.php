<?php

namespace zdi\Compiler;

use Exception;
use ReflectionFunction;

use PhpParser\BuilderFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Error as ParserError;
use PhpParser\ParserFactory;

use SuperClosure\Analyzer\Visitor\ClosureLocatorVisitor;
use SuperClosure\Exception\ClosureAnalysisException;

use zdi\ContainerInterface;
use zdi\Dependency\ClosureDependency;

class ClosureDependencyCompiler implements DependencyCompilerInterface
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var ClosureDependency
     */
    private $dependency;

    /**
     * ClosureDependencyCompiler constructor.
     * @param BuilderFactory $builderFactory
     * @param ClosureDependency $dependency
     */
    public function __construct(BuilderFactory $builderFactory, ClosureDependency $dependency)
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

        $ast = $this->locateClosure(new ReflectionFunction($dependency->getClosure()));
        $paramName = $this->getContainerParamName($ast);
        $stmts = $ast->getStmts();
        $stmts = $this->translateContainer($stmts, $paramName);
        $stmts = $this->translateReturnStatements($stmts);

        if( !$dependency->isFactory() ) {
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
            $retProp = new Node\Stmt\Return_($prop);
            array_unshift($stmts, new Node\Stmt\If_(
                new Node\Expr\Isset_(array($prop)),
                array('stmts' => array($retProp))
            ));
        }

        $method->addStmts($stmts);
        return $method;
    }

    /**
     * @param Node\Expr\Closure $ast
     * @return string|null
     * @throws \Exception
     */
    private function getContainerParamName(Node\Expr\Closure $ast)
    {
        $params = $ast->getParams();
        if( count($params) <= 0 ) {
            return null;
        } else if( count($params) > 1 ) {
            throw new \Exception("Must have only one or zero parameters");
        }
        $param = $params[0];
        $type = $param->type;

        if( !$type ) {
            trigger_error('Unspecified parameter', E_USER_WARNING);
        } else {
            if (!($type instanceof Node\Name\FullyQualified)) {
                throw new \Exception("Type must be a fully quality class name");
            }
            $paramClass = $type->toString();
            if (!is_subclass_of('\\' . $paramClass, ContainerInterface::class)) {
                throw new \Exception("Parameter must be subclass of ContainerInterface");
            }
        }
        return $param->name;
    }

    /**
     * @param Node[] $stmts
     * @param string $paramName
     * @return Node[]
     */
    private function translateContainer(array $stmts, $paramName)
    {
        $visitor = new Visitor\ContainerTranslatorVisitor($paramName);
        $fileTraverser = new NodeTraverser;
        $fileTraverser->addVisitor($visitor);
        return $fileTraverser->traverse($stmts);
    }

    /**
     * @param Node[] $stmts
     * @return Node[]
     */
    private function translateReturnStatements(array $stmts)
    {
        // Ignore factories
        if( $this->dependency->isFactory() ) {
            return $stmts;
        }

        $visitor = new Visitor\ReturnTranslatorVisitor($this->dependency->getIdentifier());
        $fileTraverser = new NodeTraverser;
        $fileTraverser->addVisitor($visitor);
        return $fileTraverser->traverse($stmts);
    }

    /**
     * @param ReflectionFunction $reflectionFunction
     * @return Node\Expr\Closure
     */
    private function locateClosure(ReflectionFunction $reflectionFunction)
    {
        try {
            $locator = new ClosureLocatorVisitor($reflectionFunction);
            $fileAst = $this->getFileAst($reflectionFunction);
            $fileTraverser = new NodeTraverser;
            $fileTraverser->addVisitor(new NameResolver);
            $fileTraverser->addVisitor($locator);
            $fileTraverser->traverse($fileAst);
        } catch (ParserError $e) {
            // @codeCoverageIgnoreStart
            throw new ClosureAnalysisException(
                'There was an error analyzing the closure code.', 0, $e
            );
            // @codeCoverageIgnoreEnd
        }
        if( !$locator->closureNode ) {
            // @codeCoverageIgnoreStart
            throw new ClosureAnalysisException(
                'The closure was not found within the abstract syntax tree.'
            );
            // @codeCoverageIgnoreEnd
        }
        return $locator->closureNode;
    }

    private function getFileAst(ReflectionFunction $reflection)
    {
        $fileName = $reflection->getFileName();
        if (!file_exists($fileName)) {
            throw new ClosureAnalysisException(
                "The file containing the closure, \"{$fileName}\" did not exist."
            );
        }
        return $this->getParser()->parse(file_get_contents($fileName));
    }

    private function getParser()
    {
        return (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
}
