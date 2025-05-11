<?php

namespace SigmaPHP\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SigmaPHP\Container\Interfaces\ContainerInterface;
use SigmaPHP\Container\Interfaces\ServiceProviderInterface;
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
     * @var array $instances 
     */
    protected $instances = [];

    /**
     * @var array $params 
     */
    protected $params = [];

    /**
     * @var array $methods 
     */
    protected $methods = [];

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
     * @var bool $isAutowiringEnabled 
     */
    protected $isAutowiringEnabled = false;

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
                $this->set((is_numeric($id) ? $definition : $id), $definition);
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
        $this->registerProvidersInTheContainer();
        $this->bootProvidersInTheContainer();
        
        if (!$this->has($id)) {
            // in case of a PHP built in class
            if ($this->isClass($id)) {
                $class = new \ReflectionClass($id);
                
                if (!$class->isUserDefined()) {
                    return new ('\\' . $id);
                }
            }

            if ($this->isAutowiringEnabled) {
                if (isset($this->instances[$id])) {
                    return $this->instances[$id];
                }
    
                $this->instances[$id] = $this->createInstance($id);
                return $this->instances[$id];
            }

            throw new NotFoundException(
                "The id \"{$id}\" is not found in the container !"
            );
        }
        
        // in case of class path , we create a new instance then we save
        // the object for future use this is like a cache mechanism 
        // instead of creating a new instance every time !
        if ($this->isClass($this->dependencies[$id])) {
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }

            $instance = $this->createInstance($id);
            $this->callInstanceSetters($id, $instance);
            
            $definition = $this->instances[$id] = $instance;
        } else {
            $definition = $this->dependencies[$id];
        }

        if ($this->isClosure($definition)) {
            return $this->resolveFactory($definition, $id);
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
        if (func_num_args() == 1) {
            if ($this->isClass($id)) {
                $definition = $id;
            } else {
                throw new ContainerException(
                    "Invalid definition : " .
                    "only classes can be accepted as a single parameter !"
                );
            }
        }

        $this->validateId($id);

        // in case the id already exists , we clear the current instance
        if (isset($this->instances[$id])) {
            unset($this->instances[$id]);
        }

        $this->dependencies[$id] = $definition;
        
        return $this;
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
            (!$this->isClass(end($this->dependencies)) &&
            !$this->isClosure(end($this->dependencies)))
        ) {
            throw new ContainerException(
                "The parameter \"{$name}\" should " . 
                "be bounded to a class constructor or a closure !"
            );
        }

        if (empty($value)) {
            // validate that the $name is a valid class path
            if (!$this->isClass($name)) {
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
            !in_array(ServiceProviderInterface::class, $interfaces)
        ) {
            throw new ContainerException(
                "Invalid provider , service provider " . 
                "MUST implement ServiceProviderInterface!"
            );
        }

        $this->providers[] = $provider;
    }

    /**
     * Register an array of service providers.
     * 
     * @param array $providers
     * @return void
     */
    public function registerProviders($providers)
    {
        if (!is_array($providers) || empty($providers)) {
            throw new \InvalidArgumentException(
                "Invalid providers for `registerProviders` method !"
            );
        }

        foreach ($providers as $provider) {
            $this->registerProvider(($provider));
        }
    }

    /**
     * Make new instance of definition.
     * 
     * @param string $id
     * @return mixed
     */
    public function make($id)
    {
        $this->registerProvidersInTheContainer();
        $this->bootProvidersInTheContainer();

        if (!$this->has($id)) {
            // in case of a PHP built in class
            if ($this->isClass($id)) {
                $class = new \ReflectionClass($id);
                
                if (!$class->isUserDefined()) {
                    return new ('\\' . $id);
                }
            }

            if ($this->isAutowiringEnabled) {
                if (isset($this->instances[$id])) {
                    return $this->instances[$id];
                }
    
                $this->instances[$id] = $this->createInstance($id);
                return $this->instances[$id];
            }

            throw new NotFoundException(
                "The id \"{$id}\" is not found in the container !"
            );
        }

        // unlike get() , make() will generate new instance of 
        // the definition every time
        if ($this->isClass($this->dependencies[$id])) {
            $instance = $this->createInstance($id);
            $this->callInstanceSetters($id, $instance);
            
            return $instance;
        } else {
            throw new ContainerException(
                "Invalid class path. \"make()\" can only be used " .
                "to create objects"
            );
        }
    }

    /**
     * Call a method in class.
     * 
     * @param string $id
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function call($id, $method, $args = [])
    {
        if (!$this->has($id)) {
            throw new NotFoundException(
                "The id \"{$id}\" is not found in the container !"
            );
        }

        if (!$this->isClass($this->dependencies[$id])) {
            throw new ContainerException(
                "\"call()\" can only be used with class methods !"
            );
        }

        $instance = $this->get($id);
        return $this->resolveMethod($id, $method, $instance, $args);
    }
    
    /**
     * Call a closure and inject all necessary dependencies.
     * 
     * @param \Closure $closure
     * @param array $args
     * @return mixed
     */
    public function callFunction($closure, $args = [])
    {
        if (!$this->isClosure($closure)) {
            throw new ContainerException(
                "\"callFunction()\" can only be used with closures !"
            );
        }

        return $this->resolveFactory($closure, '', $args);
    }
    
    /**
     * Enable autowiring.
     * 
     * @return void
     */
    public function autowire()
    {
        $this->isAutowiringEnabled = true;
    }
    
    /**
     * Check if a string is a valid class path.
     * 
     * @param string $path
     * @return bool
     */
    protected function isClass($path)
    {
        return (bool) (is_string($path) && class_exists($path));
    }

    /**
     * Check if the argument is a valid closure.
     * 
     * @param mixed $arg
     * @return bool
     */
    protected function isClosure($arg)
    {
        return (bool) (is_callable($arg) && ($arg instanceof \Closure));
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
     * Run factory methods.
     * 
     * @param \Closure $factory
     * @param string $id
     * @param array $args
     * @return mixed
     */
    protected function resolveFactory($factory, $id = '', $args = [])
    {
        $function = new \ReflectionFunction($factory);
        
        if ($function->getParameters() !== null) {
            $functionParams = isset($this->params[$id]) ? 
                $this->params[$id] : $args;

            $params = $this->getMethodParamValues(
                $function->getParameters(), 
                $functionParams
            );

            $result = $function->invoke(...$params);
        } else {
            $result = $function->invoke();
        }

        return $result;
    }
    
    /**
     * Run a method in class.
     * 
     * @param string $id
     * @param string $method
     * @param mixed $instance
     * @param array $args
     * @return mixed
     */
    protected function resolveMethod($id, $method, $instance, $args = [])
    {
        $methodRef = new \ReflectionMethod($this->dependencies[$id], $method);
        $result = null;

        if ($methodRef->getParameters() !== null) {
            $methodArgs = $this->getMethodParamValues(
                $methodRef->getParameters(), 
                $args
            );
            
            $result = $methodRef->invoke($instance, ...$methodArgs);
        } else {
            $result = $methodRef->invoke($instance);
        }

        return $result;
    }
    
    /**
     * Get method's parameters values , by injecting all required
     * dependencies and resolve any closures.
     * 
     * @param \ReflectionParameter[] $params
     * @param array $args
     * @return array
     */
    protected function getMethodParamValues($params, $args = [])
    {
        $methodArgs = [];
        foreach ($params as $param) {
            $paramClassName = '';
            $paramIsBuiltIn = false;

            // in case of multiple param types , like union and intersection
            // we always resolve first type 
            if (($param->getType() !== null)) {
                if (!($param->getType() instanceof \ReflectionNamedType)) {
                    $paramClassName = $param->getType()->getTypes()[0]
                        ->getName();
                    $paramIsBuiltIn = $param->getType()->getTypes()[0]
                        ->isBuiltin();
                } else {
                    $paramClassName = $param->getType()->getName();
                    $paramIsBuiltIn = $param->getType()->isBuiltin();
                }
            }

            // check if parameter is a primitive or a class !!
            if (($paramClassName !== '') && !$paramIsBuiltIn) {
                $dependency = $paramClassName;

                if (isset($args[$param->name])) {
                    if ($this->isClass($args[$param->name])) {
                        $methodArgs[] = $this->get($dependency);
                    } else {
                        $methodArg = $args[$param->name];

                        if ($this->isClosure($methodArg)) {                        
                            $methodArgs[] = $this->resolveFactory($methodArg);
                        } else {
                            $methodArgs[] = $methodArg;
                        }
                    }
                } else {
                    // in case the factory accept the container as a parameter
                    // we pass the current container as parameter
                    if ($dependency == get_class($this)) {
                        $methodArgs[] = $this;
                    } else {
                        if ($this->isAutowiringEnabled) {
                            if ($this->has($dependency) || 
                                $this->isClass($dependency)
                            ) {
                                $methodArgs[] = $this->get($dependency);
                            }
                        } else {
                            $methodArgs[] = $this->get($dependency);
                        }
                    }
                }
            } else {
                if (isset($args[$param->name])) {
                    $methodArgs[] = $args[$param->name];
                }
            }
        }

        return $methodArgs;
    }

    /**
     * Register service providers.
     * 
     * @return void
     */
    protected function registerProvidersInTheContainer()
    {
        if (!$this->providersAreRegistered) {
            foreach ($this->providers as $provider) {
                $serviceProvider = new ('\\' . $provider);
                $serviceProvider->register($this);
            }

            $this->providersAreRegistered = true;
        }
    }
    
    /**
     * Boot service providers.
     * 
     * @return void
     */
    protected function bootProvidersInTheContainer()
    {
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
    }
    
    /**
     * Create new instance from a class.
     * 
     * @param string $id
     * @return mixed
     */
    protected function createInstance($id)
    {
        if ($this->isAutowiringEnabled && !$this->has($id)) {
            $className = '\\' . $id;
        } else {
            $className = $this->dependencies[$id];
        }

        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $instance = null;

        $dependencyParams = isset($this->params[$id]) ? $this->params[$id] : [];

        if ($constructor !== null) {
            $constructorParams = $this->getMethodParamValues(
                $constructor->getParameters(), 
                $dependencyParams
            );

            $instance = $class->newInstanceArgs($constructorParams);
        } else {
            $instance = $class->newInstance();
        }

        return $instance;
    }

    /**
     * Create new instance from a class.
     * 
     * @param string $id
     * @param mixed $instance
     * @return void
     */
    protected function callInstanceSetters($id, $instance)
    {
        if (!isset($this->methods[$id]) || (count($this->methods[$id]) < 1)) {
            return;
        }

        foreach ($this->methods[$id] as $name => $args) {
            return $this->resolveMethod($id, $name, $instance, $args);
        }
    }
}