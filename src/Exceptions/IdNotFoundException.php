<?php

namespace SigmaPHP\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Id Not Found Exception
 */
class IdNotFoundException extends \Exception implements NotFoundExceptionInterface
{}