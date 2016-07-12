<?php

namespace zdi\Tests;

use PhpParser\Node;

use zdi\Exception\DomainException;
use zdi\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testClassToIdentifier()
    {
        $this->assertSame('ZdiUtils', Utils::classToIdentifier(Utils::class));
        $this->assertSame('LetSGoCrazy', Utils::classToIdentifier('let\'s go crazy!'));
    }

    public function testClassToIdentifierWithValidIdentifier()
    {
        $this->assertSame('aValidIdentifier', Utils::classToIdentifier('aValidIdentifier'));
    }

    public function testIsValidIdentifier()
    {
        $this->assertTrue(Utils::isValidIdentifier('some_function'));
        $this->assertTrue(Utils::isValidIdentifier('someOtherFunction'));
        $this->assertFalse(Utils::isValidIdentifier('4lyfe'));
        $this->assertFalse(Utils::isValidIdentifier('1488'));
        $this->assertFalse(Utils::isValidIdentifier('UJT()#@$U%R)#@($U%*'));
    }

    public function testParserNodeFromValueTrue()
    {
        $node = Utils::parserNodeFromValue(true);
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Name::class, $node->name);
        $this->assertSame(array('true'), $node->name->parts);
    }

    public function testParserNodeFromValueFalse()
    {
        $node = Utils::parserNodeFromValue(false);
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Name::class, $node->name);
        $this->assertSame(array('false'), $node->name->parts);
    }

    public function testParserNodeFromValueNull()
    {
        $node = Utils::parserNodeFromValue(null);
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Name::class, $node->name);
        $this->assertSame(array('null'), $node->name->parts);
    }

    public function testParserNodeFromValueInteger()
    {
        $node = Utils::parserNodeFromValue(1488);
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Scalar\LNumber::class, $node);
        $this->assertSame(1488, $node->value);
    }

    public function testParserNodeFromValueFloat()
    {
        $node = Utils::parserNodeFromValue(14.88);
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Scalar\DNumber::class, $node);
        $this->assertSame(14.88, $node->value);
    }

    public function testParserNodeFromValueString()
    {
        $node = Utils::parserNodeFromValue('1488');
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Scalar\String_::class, $node);
        $this->assertSame('1488', $node->value);
    }

    public function testParserNodeFromValueEmptyArray()
    {
        $node = Utils::parserNodeFromValue(array());
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Expr\Array_::class, $node);
    }

    public function testParserNodeFromValueArray()
    {
        $node = Utils::parserNodeFromValue(array(1 => 2, 'a' => 'b'));
        $this->assertInstanceOf(Node\Expr::class, $node);
        $this->assertInstanceOf(Node\Expr\Array_::class, $node);
        $this->assertSame(2, count($node->items));
        foreach( $node->items as $arrayItem ) {
            if( $arrayItem->key instanceof Node\Scalar\LNumber ) {
                $this->assertInstanceOf(Node\Scalar\LNumber::class, $arrayItem->value);
                $this->assertSame($arrayItem->value->value, 2);
            } else if( $arrayItem->key instanceof Node\Scalar\String_ ) {
                $this->assertInstanceOf(Node\Scalar\String_::class, $arrayItem->value);
                $this->assertSame($arrayItem->value->value, 'b');
            } else {
                throw new \Exception('Unknown key: ' . $arrayItem->key);
            }
        }
    }

    public function testParserNodeFromValueInvalid()
    {
        $this->setExpectedException(DomainException::class);
        Utils::parserNodeFromValue(tmpfile());
    }

    public function testExtractNamespace()
    {
        $this->assertSame(array(null, \stdClass::class), Utils::extractNamespace(\stdClass::class));
        $this->assertSame(array('Foo', 'Bar'), Utils::extractNamespace('Foo\\Bar'));
    }
}
