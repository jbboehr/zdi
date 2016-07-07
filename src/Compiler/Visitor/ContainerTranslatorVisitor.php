<?php

namespace zdi\Compiler\Visitor;

use PhpParser\Node as AstNode;
use PhpParser\NodeVisitorAbstract as NodeVisitor;
use PhpParser\Node\Expr\Variable;

class ContainerTranslatorVisitor extends NodeVisitor
{
    private $varname;

    public function __construct($varname)
    {
        $this->varname = $varname;
    }

    public function enterNode(AstNode $node)
    {
        if( $node instanceof Variable ) {
            if( $node->name === $this->varname ) {
                $node->name = 'this';
            }
        }
    }
}
