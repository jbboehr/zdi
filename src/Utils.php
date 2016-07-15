<?php

namespace zdi;

use PhpParser\Node;

class Utils
{
    /**
     * @param string $class
     * @return string
     */
    static public function classToIdentifier($class)
    {
        if( static::isValidIdentifier($class) ) {
            return $class;
        }
        return str_replace(' ', '', ucwords(preg_replace('/[^a-z0-9]+/i', ' ', $class)));
        //return str_replace(' ', '', ucwords(str_replace(array('\\', '_'), ' ', $class)));
    }

    /**
     * @param string $className
     * @return string[]
     */
    static public function extractNamespace($className)
    {
        $pos = strrpos($className, '\\');
        if( false === $pos ) {
            $namespace = null;
            $class = $className;
        } else {
            $namespace = substr($className, 0, $pos);
            $class = substr($className, $pos + 1);
        }
        return array($namespace, $class);
    }

    /**
     * @param string $class
     * @return boolean
     */
    static public function isValidIdentifier($class)
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]+$/i', $class);
    }

    /**
     * @param mixed $value
     * @return Node\Expr
     */
    static public function parserNodeFromValue($value)
    {
        if( is_array($value) ) {
            $items = array();
            foreach( $value as $k => $v ) {
                $items[] = new Node\Expr\ArrayItem(static::parserNodeFromValue($v), static::parserNodeFromValue($k));
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
            throw new Exception\DomainException('Unsupported value: ' . Utils::varInfo($value));
        }
    }

    /**
     * @param array $definitions
     * @param Definition $definition
     * @param boolean $isOptional
     * @return null|Definition
     * @throws Exception\DomainException
     */
    static public function resolveAlias(array $definitions, Definition $definition, $isOptional = false)
    {
        while( $definition instanceof Definition\AliasDefinition ) {
            $definition = self::resolveDefinition($definitions, $definition->getAlias(), $isOptional);
        }
        return $definition;
    }

    /**
     * @param Definition[] $definitions
     * @param string $key
     * @param boolean $isOptional
     * @return null|Definition
     */
    static public function resolveAliasKey(array $definitions, $key, $isOptional = false)
    {
        $definition = Utils::resolveDefinition($definitions, $key, $isOptional);
        if( $definition ) {
            $definition = Utils::resolveAlias($definitions, $definition, $isOptional);
        }
        return $definition;
    }

    /**
     * @param Definition[] $definitions
     * @param string $key
     * @param boolean $isOptional
     * @return null|Definition
     */
    static public function resolveDefinition(array $definitions, $key, $isOptional = false)
    {
        if( isset($definitions[$key]) ) {
            return $definitions[$key];
        } else if( $isOptional ) {
            return null;
        } else {
            throw new Exception\OutOfBoundsException("Undefined identifier: " . $key);
        }
    }

    /**
     * @param Definition[] $definitions
     * @param string $key
     * @param boolean $isOptional
     * @return Definition
     */
    static public function resolveGlobalKey(array $definitions, $key, $isOptional = false)
    {
        $definition = Utils::resolveAliasKey($definitions, $key, true);
        if( $definition && $definition->isGlobal() ) {
            return $definition;
        } else if( !$isOptional ) {
            throw new Exception\OutOfBoundsException("Undefined identifier: " . $key);
        }
        return null;
    }

    /**
     * @param mixed $var
     * @return string
     */
    static public function varInfo($var)
    {
        if( is_object($var) ) {
            return get_class($var);
        } else {
            return gettype($var);
        }
    }
}
