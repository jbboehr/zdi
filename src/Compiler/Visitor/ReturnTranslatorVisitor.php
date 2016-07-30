<?php

namespace zdi\Compiler\Visitor;

use PhpParser\Node as AstNode;
use PhpParser\NodeVisitorAbstract as NodeVisitor;
use PhpParser\Node;

class ReturnTranslatorVisitor extends NodeVisitor
{
    private $var;

    private $stmts;

    public function __construct($var, $stmts)
    {
        $this->var = $var;
        $this->stmts = $stmts;
    }

    public function beforeTraverse(array $nodes)
    {
        $newNodes = array();
        foreach( $nodes as $node ) {
            if( $node instanceof Node\Stmt\Return_ ) {
                $newNodes[] = new Node\Expr\Assign(clone $this->var, $node->expr);
                foreach( $this->stmts as $stmt ) {
                    $newNodes[] = clone $stmt;
                }
                $newNodes[] = new Node\Stmt\Return_(clone $this->var);
            } else {
                $newNodes[] = $node;
            }
        }
        return $newNodes;
    }
}
