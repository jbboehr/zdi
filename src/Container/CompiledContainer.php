<?php

namespace zdi\Container;

use zdi\Container;
use zdi\Exception;

abstract class CompiledContainer implements Container
{
    private $values;

    static protected $map;

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if( isset(static::$map[$key]) ) {
            return $this->{static::$map[$key]}();
        } else if( isset($this->values[$key]) ) {
            return $this->values[$key];
        } else {
            throw new Exception\OutOfBoundsException("Undefined identifier: " . $key);
        }
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return isset(static::$map[$key]) || isset($this->values[$key]);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }

    /*
    public function __call($method, $arguments)
    {
        throw new Exception('Undefined identifier: ' . $method);
    }
    */
}
