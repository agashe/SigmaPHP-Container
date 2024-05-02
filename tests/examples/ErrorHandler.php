<?php

namespace SigmaPHP\Container\Tests\Examples;

class ErrorHandler
{
    /**
     * @var \Exception $error
     */
    protected $error;

    /**
     * ErrorHandler Constructor
     * 
     * @param \Exception $error
     */
    public function __construct(\Exception $error)
    {
        $this->error = $error;
    }

    /**
     * Print error.
     * 
     * @param \Exception $e
     * @return void
     */
    public function printErrorMessage(\Exception $e = null)
    {
        if (empty($e)) {
            $e = $this->error;
        }
        
        $message = $e->getMessage() ?: 'Exception';

        echo "Help !! {$message}" . PHP_EOL;
    }
}