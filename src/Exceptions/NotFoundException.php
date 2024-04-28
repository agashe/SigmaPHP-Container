<?php

namespace SigmaPHP\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Not Found Exception
 */
class NotFoundException extends \Exception implements 
    NotFoundExceptionInterface
{}