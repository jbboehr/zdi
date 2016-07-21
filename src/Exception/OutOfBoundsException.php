<?php

namespace zdi\Exception;

use zdi\Exception;
use OutOfBoundsException as BaseOutOfBoundsException;
use Interop\Container\Exception\NotFoundException;

class OutOfBoundsException extends BaseOutOfBoundsException implements Exception, NotFoundException
{

}
