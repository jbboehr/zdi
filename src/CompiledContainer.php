<?php

namespace zdi;

abstract class CompiledContainer extends AbstractContainer implements ContainerInterface
{
    private $values;

    static protected $map;

    /**
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset(static::$map[$key]) || isset($this->values[$key]);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function get($key)
    {
        if( isset(static::$map[$key]) ) {
            return $this->{static::$map[$key]}();
        } else if( isset($this->values[$key]) ) {
            return $this->values[$key];
        } else {
            throw new \Exception("Undefined identifier: " . $key);
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }
}
