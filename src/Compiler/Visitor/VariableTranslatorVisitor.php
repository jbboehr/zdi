<?php

namespace zdi\Compiler\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract as NodeVisitor;
use PhpParser\Node\Expr\Variable;
use zdi\Exception;
use zdi\InjectionPoint;

class VariableTranslatorVisitor extends NodeVisitor
{
    private $map;

    public function __construct($map)
    {
        $this->map = $map;
    }

    public function enterNode(Node $node)
    {
        if( $node instanceof Variable ) {
            if( isset($this->map[$node->name]) ) {
                $new = $this->map[$node->name];
                if( $new instanceof InjectionPoint ) {
                    // ignore for now
                } else {
                    return clone $this->map[$node->name];
                }
            }
        } else if( $node instanceof Node\Expr\PropertyFetch ) {
            $var = $node->var;
            if( isset($this->map[$var->name]) ) {
                $new = $this->map[$var->name];
                if( $new instanceof InjectionPoint ) {
                    if( $node->name === 'class' ) {
                        return new Node\Expr\Variable('ipClass');
                    } else if( $node->name === 'method' ) {
                        return new Node\Expr\Variable('ipMethod');
                    } else {
                        throw new Exception\DomainException('Unknown property of InjectionPoint class: ' . $node->name);
                    }
                }
            }
        }
    }
}
