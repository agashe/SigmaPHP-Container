<?php

namespace SigmaPHP\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SigmaPHP\Container\Interfaces\ContainerInterface;
use SigmaPHP\Container\Interfaces\ProviderInterface;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\NotFoundException;

/**
 * Container Class
 */
class Container implements PsrContainerInterface , ContainerInterface
{
    /**
     * @var array $dependencies 
     */
    protected $dependencies = [];

    /**
     * @var array $params 
     */
    protected $params = [];

    /**
     * @var array $methods 
     */
    protected $methods = [];

    /**
     * @var array $values 
     */
    protected $values = [];

    /**
     * @var array $providers 
     */
    protected $providers = [];
    
    /**
     * @var bool $providersAreRegistered 
     */
    protected $providersAreRegistered = false;

    /**
     * @var bool $providersAreBooted 
     */
    protected $providersAreBooted = false;
    
    /**
     * @var bool $isBootingProviders 
     */
    protected $isBootingProviders = false;

    /**
     * Container Constructor.
     * 
     * @param array $definitions 
     */
    public function __construct($definitions = []) {
        foreach ($definitions as $id => $definition) {
            if (is_array($definition)) {
                $this->set($id, $definition['definition']);

                if (isset($definition['params'])) {
                    foreach ($definition['params'] as $name => $value) {
                        $this->setParam($name, $value);
                    }
                }

                if (isset($definition['methods'])) {
                    foreach ($definition['methods'] as $name => $args) {
                        $this->setMethod($name, $args);
                    }
                }
            } else {
                $this->set($id, $definition);
            }
        }
    }

    /**
     * Get an instance for a definition from the container.
     * 
     * @param string $id
     * @return mixed 
     */
    public function get($id)
    {
        // register providers , if not registered yet
        if (!$this->providersAreRegistered) {
            foreach ($this->providers as $provider) {
                $serviceProvider = new ('\\' . $provider);
                $serviceProvider->register($this);
            }

            $this->providersAreRegistered = true;
        }

        if (!$this->has($id)) {
            throw new NotFoundException(
                "The id \"{$id}\" is not found in the container !"
            );
        }

        // in case of class path , we create a new instance then we save
        // the object for future use this is like a cache mechanism 
        // instead of creating a new instance every time !
        if (is_string($this->dependencies[$id])) {
            $class = new \ReflectionClass($this->dependencies[$id]);
            $constructor = $class->getConstructor();
            $instance = null;

            $dependencyParams = isset($this->params[$id]) ?
                $this->params[$id] : [];

            if ($constructor !== null) {
                $constructorParams = [];
                
                // loop throw all args , and inject dependencies
                foreach ($constructor->getParameters() as $parameter) {
                    // check if parameter is a primitive or a class !!
                    if ($parameter->getType() !== null) {
                        $dependency = $parameter->getType()->getName();
                        
                        if (isset($dependencyParams[$parameter->name])) {
                            if (is_string(
                                    $dependencyParams[$parameter->name]
                                ) &&
                                class_exists(
                                    $dependencyParams[$parameter->name]
                                )
                            ) {
                                $constructorParams[] = $this->get($dependency);
                            } else {
                                $dependencyParam = 
                                    $dependencyParams[$parameter->name];

                                if (is_callable($dependencyParam) &&
                                    ($dependencyParam instanceof \Closure)
                                ) {
                                    // check if factory accept the container 
                                    // as a parameter
                                    $function = new \ReflectionFunction(
                                        $dependencyParam
                                    );
                                    
                                    if ($function->getParameters() !== null) {
                                        $result = $dependencyParam($this);
                                    } else {
                                        $result = $dependencyParam();
                                    }
                        
                                    $constructorParams[] = $result;
                                } else {
                                    $constructorParams[] = 
                                        $dependencyParams[$parameter->name];
                                }
                            }
                        } else {
                            $constructorParams[] = $this->get($dependency);
                        }
                    } else {
                        if (isset($dependencyParams[$parameter->name])) {
                            $constructorParams[] = 
                                $dependencyParams[$parameter->name];
                        }
                    }
                }

                $instance = $class->newInstanceArgs($constructorParams);
            } else {
                $instance = $class->newInstance();
            }

            // call any setter methods related to the definition
            if (isset($this->methods[$id]) && 
                (count($this->methods[$id]) > 0)
            ) {
                foreach ($this->methods[$id] as $name => $args) {
                    $method = new \ReflectionMethod($id, $name);
                    
                    if ($method->getParameters() !== null) {
                        $methodArgs = [];

                        foreach ($method->getParameters() as $parameter) {
                            // check if parameter is a primitive or a class !!
                            if ($parameter->getType() !== null) {
                                $dependency = $parameter->getType()->getName();
                                
                                if (isset($args[$parameter->name])) {
                                    if (is_string($args[$parameter->name]) &&
                                        class_exists($args[$parameter->name])
                                    ) {
                                        $methodArgs[] = $this->get($dependency);
                                    } else {
                                        $methodArg = $args[$parameter->name];

                                        if (is_callable($methodArg) &&
                                            ($methodArg instanceof \Closure)
                                        ) {
                                            // check if factory accept the  
                                            // container as a parameter
                                            $function = new \ReflectionFunction(
                                                $methodArg
                                            );
                                            
                                            if ($function->getParameters() 
                                                !== null
                                            ) {
                                                $result = $methodArg($this);
                                            } else {
                                                $result = $methodArg();
                                            }
                                
                                            $methodArgs[] = $result;
                                        } else {
                                            $methodArgs[] =
                                                $args[$parameter->name];
                                        }
                                    }
                                }
                            } else {
                                if (isset($args[$parameter->name])) {
                                    $methodArgs[] = $args[$parameter->name];
                                }
                            }
                        }

                        $method->invoke($instance, ...$methodArgs);
                    } else {
                        $method->invoke($instance);
                    }
                }
            }

            $this->dependencies[$id] = $instance;
        }
        
        $definition = $this->dependencies[$id];

        // boot service providers
        if ($this->providersAreRegistered && 
            !$this->providersAreBooted && 
            !$this->isBootingProviders
        ) {
            foreach ($this->providers as $provider) {
                $serviceProvider = new ('\\' . $provider);

                // we implement some kind of locking mechanism  
                // so the booting function never get caught
                // in infinite recursion !!

                $this->isBootingProviders = true;

                $serviceProvider->boot($this);
                
                $this->isBootingProviders = false;
            }

            $this->providersAreBooted = true;
        }

        if (is_callable($definition) && ($definition instanceof \Closure)) {
            // check if factory accept the container as a parameter
            $function = new \ReflectionFunction($definition);
            
            if ($function->getParameters() !== null) {
                $result = $definition($this);
            } else {
                $result = $definition();
            }

            return $result;
        }

        return $definition;
    }

    /**
     * Check if a definition exists in the container.
     * 
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        return in_array($id, array_keys($this->dependencies));
    }

    /**
     * Add new definition for a class to the container.
     * 
     * @param string $id
     * @param mixed $definition
     * @return self 
     */
    public function set($id, $definition = null)
    {
        if (empty($definition)) {
            $definition = $id;
        }

        $this->validateId($id);
        $this->validateDefinition($definition);
        
        $this->dependencies[$id] = $definition;
        
        return $this;
    }

    /**
     * Check that the id is valid.
     * 
     * We have 3 valid types of ids so far :
     * - class path (e.g, Mailer::class) 
     * - interface path (e.g, MailerInterface::class) 
     * - string alias (e.g, 'mailer') 
     * 
     * @param string $id
     * @return void
     */
    protected function validateId($id)
    {
        if (!is_string($id) || empty($id)) {
            throw new ContainerException(
                "Invalid id. Id can only accept string values !"
            );
        }
    }

    /**
     * Check that the definition is valid.
     * 
     * We have 3 valid types of definitions so far :
     * - class path (e.g, Mailer::class) 
     * - callback functions "factories" (e.g, fn() => {...}) 
     * - objects (e.g, new Mailer()) 
     * 
     * @param string $definition
     * @return void
     */
    protected function validateDefinition($definition)
    { 
        $invalid = false;

        if (is_string($definition)) {
            if (empty($definition) || !class_exists('\\' . $definition)) {
                $invalid = true;
            }
        } else {
            if (!is_callable($definition) && !is_object($definition)) {
                $invalid = true;
            }
        }
        
        if ($invalid) {
            throw new ContainerException(
                "Invalid definition : " .
                "only classes , objects and callbacks are accepted !"
            );
        }
    }

    /**
     * Set a parameter or bind it to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setParam($name, $value = null)
    {
        if (empty($this->dependencies) ||
            !class_exists(end($this->dependencies))
        ) {
            throw new ContainerException(
                "The parameter \"{$name}\" should " . 
                "be bounded to a class constructor !"
            );
        }

        if (empty($value)) {
            // validate that the $name is a valid class path
            if (!is_string($name) || !class_exists($name)) {
                throw new ContainerException(
                    "Only class path can be passed as single parameters !"
                );
            }
            
            $value = $name;
        }

        $this->params[array_key_last($this->dependencies)][$name] = $value;

        return $this;
    }

    /**
     * Bind a method to a definition.
     * 
     * @param string $name
     * @param mixed $args
     * @return self
     */
    public function setMethod($name, $args = [])
    {   
        if (empty($this->dependencies) ||
            !class_exists(end($this->dependencies))
        ) {
            throw new ContainerException(
                "The method \"{$name}\" should be bounded to a class !"
            );
        }
        
        $this->methods[array_key_last($this->dependencies)][$name] = $args;

        return $this;       
    }

    /**
     * Set a constant value in the container.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setValue($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * Get a constant value from in the container.
     * 
     * @param string $name
     * @return mixed
     */
    public function getValue($name)
    {
        if (!in_array($name, array_keys($this->values))) {
            throw new NotFoundException(
                "The constant value \"{$name}\" is not found in the container !"
            );
        }

        return $this->values[$name];
    }

    /**
     * Register a service provider.
     * 
     * @param string $provider
     * @return void
     */
    public function registerProvider($provider)
    {
        // the provider should be a valid class , and MUST implement
        // the service provider interface
        if (!is_string($provider) ||
            empty($provider) ||
            !class_exists($provider)
        ) {
            throw new ContainerException(
                "Invalid provider , service provider should be a valid class !"
            );
        }

        $interfaces = class_implements($provider);

        if (empty($interfaces) ||
            !in_array(ProviderInterface::class, $interfaces)
        ) {
            throw new ContainerException(
                "Invalid provider , service provider " . 
                "should MUST implement ProviderInterface!"
            );
        }

        $this->providers[] = $provider;
    }
}