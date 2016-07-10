<?php

namespace zdi\Compiler\Visitor;

use PhpParser\Node as AstNode;
use PhpParser\NodeVisitorAbstract as NodeVisitor;
use PhpParser\Node\Expr\Variable;

class VariableTranslatorVisitor extends NodeVisitor
{
    private $map;

    public function __construct($map)
    {
        $this->map = $map;
    }

    public function enterNode(AstNode $node)
    {
        if( $node instanceof Variable ) {
            if( isset($this->map[$node->name]) ) {
                return clone $this->map[$node->name];
            }
        }
    }
}
