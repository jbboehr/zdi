<?php

namespace zdi;

class Utils
{
    static public function classToIdentifier($class)
    {
        if( static::isValidIdentifier($class) ) {
            return $class;
        }
        return str_replace(' ', '', ucwords(preg_replace('/[^a-z0-9]+/i', ' ', $class)));
        //return str_replace(' ', '', ucwords(str_replace(array('\\', '_'), ' ', $class)));
    }

    static public function isValidIdentifier($class)
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]+$/i', $class);
    }
}
