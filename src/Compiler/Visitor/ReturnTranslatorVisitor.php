<?php

namespace zdi\Compiler\Visitor;

use PhpParser\Node as AstNode;
use PhpParser\NodeVisitorAbstract as NodeVisitor;
use PhpParser\Node;

class ReturnTranslatorVisitor extends NodeVisitor
{
    private $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function enterNode(AstNode $node)
    {
        /* if( $node instanceof Node\Expr\Closure ) {
            throw new \Exception("Cannot nest closures");
        } else */ if( $node instanceof Node\Stmt\Return_ ) {
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $this->identifier);
            $newNode = new Node\Stmt\Return_(new Node\Expr\Assign($prop, $node->expr));
            return $newNode;
        }
    }
}
