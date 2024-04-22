<?php

namespace SigmaPHP\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Parameter Not Found Exception
 */
class ParameterNotFoundException extends \Exception implements 
    NotFoundExceptionInterface
{}