<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Exception;

abstract class AbstractDefinitionCompiler implements DefinitionCompiler
{
    /**
     * @var BuilderFactory
     */
    protected $builderFactory;

    /**
     * @var Definition
     */
    protected $definition;

    /**
     * @var Definition[]
     */
    protected $definitions;

    /**
     * @var \ArrayAccess|array
     */
    protected $astCache;

    /**
     * @param BuilderFactory $builderFactory
     * @param Definition $definition
     * @param Definition[] $definitions
     */
    final public function __construct(BuilderFactory $builderFactory, Definition $definition, array $definitions, $astCache)
    {
        $this->builderFactory = $builderFactory;
        $this->definition = $definition;
        $this->definitions = $definitions;
        $this->astCache = $astCache;
    }

    /**
     * @return Node\Stmt\If_
     */
    protected function makeSingletonCheck()
    {
        $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $this->definition->getIdentifier());
        return new Node\Stmt\If_(
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\ConstFetch(new Node\Name('null')),
                clone $prop
            ),
            array('stmts' => array(new Node\Stmt\Return_(clone $prop)))
        );
    }
}
