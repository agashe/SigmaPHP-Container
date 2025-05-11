# SigmaPHP-Container

A dependency injection container for PHP. Dependency injection is a powerful design pattern , which can be used to reduce coupling , implement inversion of control and enhance unit testing.

SigmaPHP-Container provides useful set of features , which will take your project to the next level. By removing coupling between your services , allowing you to create more SOLID application.

No more "new" in your constructors , all what you have to do is to add the type hint before the parameter's name. and we are done ! the container will take care of the rest.

And last but not least , the service providers , a powerful mechanism , that can add the extendability to your application , so other developers could create plugins and extensions for your application and then register them so easily. 

## Features

* Compliant with [PSR-11](https://www.php-fig.org/psr/psr-11/)
* Custom definitions for dependencies 
* Support constructor and setters injection
* Support factories for in depth customization
* Ability to add dependencies using Interfaces and aliases
* Service providers for better code structure
* Call methods in classes with injection 
* Inject dependencies to closures
* Autowiring with zero configuration !

## Installation

``` 
composer require agashe/sigmaphp-container
```

## Documentation

#### Table of Contents  
* [Basic usage](#basic-usage)  
* [Constructor injection](#constructor-injection)
* [Setter injection](#setter-injection)
* [Bind parameters](#bind-parameters)
* [Definitions](#definitions)  
* [Shared instances](#shared-instances)  
* [Factories](#factories)
* [Call a method in class](#call-a-method-in-class)
* [Call a closure](#call-a-closure)
* [Service providers](#service-providers)
* [Autowiring](#autowiring) 


### Basic usage

To start using the container , we start by defining a new instance of the `Container` class , in which we are going to define our dependencies , and then request instances :

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Container\Container;
use MyApp\MailerService;

$container = new Container();

$container->set(MailerService::class);

if ($container->has(MailerService::class)) {
    $mailerService = $container->get(MailerService::class);
}
```

In the example above we define our dependency `MyApp\MailerService` in the container , then we try to define a new instance from that dependency.

So the three basic methods we use to interact with the container are :

* `set(string $id, mixed $definition): void` <br>
    To add an new definition to the container 

* `get(string $id): mixed` <br>
    To add an new definition to the container 

* `has(string $id): bool` <br>
    We use this method to check if a specific definition is provided by the container or not. 

The `$id` is the keyword we use to request the dependencies , and it supports 3 main types :

* A class Path `MailerService::class`
* A non-class path like interfaces , abstract and traits `AuthTrait::class` , `MailerServiceInterface::class`
* An alias , which is just a regular string 

For the `$definition` it could be any valid PHP data type or class , even `NULL` , so in the example below all these definitions and ids are valid :

```
$container->set('mailer', MailerService::class);
$container->set(MailerServiceInterface::class, MailerService::class);
$container->set('PI', 3.14);
$container->set('odd_numbers', [1, 3, 5, 7]);
$container->set('my_exception', (new \Exception('whatever...')));
$container->set('_function', fn() => 5+6);
```
One exception for the `set()` method is that in case of class path , it could accept only the `$id` :

```
// it doesn't make any sense to do this , although it's valid :D
$container->set(MailerService::class, MailerService::class);

// instead we use
$container->set(MailerService::class);
```


### Constructor injection

The main type of any dependency injection in any container , the injection through constructor. Which means the ability to inject every required type of parameter to each class constructor in our application upon requesting new instance from that class.  

Assume that we have a `UserModel` in our app , that we need to create a new instance from. This `UserModel` requires 2 dependencies , a DB connection and a mailer service :

```
<?php

use MyApp\MailerService;
use MyApp\DbConnection;

class UserModel
{
    private $conn;
    private $mailer;

    public function __construct()
    {
        $this->conn = new DbConnection();
        $this->mailer = new MailerService();
    }
}
```

A very straight forward class , that we usually encounter in our projects , and we can notice how the coupling here is very high and the testing of course is imposable without a real database connection and mailer service :(

So let's see how using the container could help us , make this process less painful. 

The first step we need to understand is that the dependency injection pattern , that it depends heavily on type hinting , so without a type hinting , it's imposable for the container to decide which class should be injected ! 

So keep in mind , that you need to add the type hinting for your constructor parameters in order for the container to inject the correct dependency.  

```
class UserModel
{
    private $conn;
    private $mailer;

    public function __construct(DbConnection $conn, MailerService $mailer) 
    {
        $this->conn = $conn;
        $this->mailer = $mailer;
    }
}
```

So after updating our `UserModel` , we could use the container to define the instances :

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Container\Container;
use MyApp\MailerService;
use MyApp\DbConnection;
use MyApp\UserModel;

$container = new Container();

$container->set(MailerService::class);
$container->set(DbConnection::class);
$container->set(UserModel::class);

$user = $container->get(UserModel::class);
```

As we can see , the container added a bonus flexibility to our application , and we can now easily control the injected classes , by replacing them if needed , and for testing we can create dummy services and inject it to the class under testing so easily. 

### Setter injection

Another popular type of dependency injection is the setter methods , in this type the class will have a separated methods to inject the dependency instead of defining it in the constructor.

So we can re-write the `UserModel` with setter methods as following :

```
class UserModel
{
    private $conn;
    private $mailer;

    public function __construct()
    {}
    
    public function setDbConnection(DbConnection $conn)
    {
        $this->conn = $conn;
    }
    
    public function setMailerService(MailerService $mailer)
    {
        $this->mailer = $mailer;
    }
}
```
The container provides the `setMethod` helper. Any method defined using this helper will be called whenever the container is requested to create to new instance from that class.

```
setMethod(string $name, array $args = []): void
```
The `setMethod` accepts 2 parameters , the first one is the `$name` which is the setter method name. The second is an optional parameter `$args` , an associative array contains the names and values of the  setter method parameters.

So let's try define our `UserModel` class in the container :

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Container\Container;
use MyApp\MailerService;
use MyApp\DbConnection;
use MyApp\UserModel;

$container = new Container();

$container->set(MailerService::class);
$container->set(DbConnection::class);

$container->set(UserModel::class)
    ->setMethod('setDbConnection')
    ->setMethod('setMailerService');

$user = $container->get(UserModel::class);
```

As we can notice since `setDbConnection` and `setMailerService` both defining their parameters using type hinting , no additional parameter is required; And the container will automatically will resolve the required dependencies.

But assume that we have some kind of a setter that requires primitive values. We can easily pass these parameters using the `$args` parameter :

```
// Shape class
class Shape
{
    public function setDimensions($height, $width, $length)
    {/* ... */}
}

// set in the container
$container = new Container();

$container->set(Shape::class)
    ->setMethod('setDimensions', [
        'height' => 10,
        'width'  => 20,
        'length' => 30,
    ]);
```

And also as a bonus point , the `$args` could also accept those parameters with type hinting. So now you have more control over the setter methods :

```
$container->set(UserModel::class)
    ->setMethod('setDbConnection', [
        'conn' => function () { 
            return new \PDO(....);
        }
    ])
    ->setMethod('setMailerService', [
        'mailer' => (new MailerService())
    ]);
```

### Bind parameters

In both constructor injection and setter method injection , we saw how the container could automatically resolve any parameter using the type hinting. 

And also how can we customize the `setMethod` parameters. and handle the case of primitive parameters for the setter method injection.

But what about the constructor injection , what if we have a class which requires a primitive parameters (strings , int , bool ....etc) ?

The container provides the `setParam` so we can pass a specific parameter for the class's constructor :

```
setParam(string $name, mixed $value = null): void
```
Let's check the `UserModel` example :

```
class UserModel
{
    private $conn;
    private $mailer;

    public function __construct(DbConnection $conn, MailerService $mailer) 
    {
        $this->conn = $conn;
        $this->mailer = $mailer;
    }
}
```

So instead of letting the container resolving we can do this our selves : 

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Container\Container;
use MyApp\MailerService;
use MyApp\DbConnection;
use MyApp\UserModel;

$container = new Container();

$container->set(MailerService::class);
$container->set(DbConnection::class);

$container->set(UserModel::class)
    ->setParam(MailerService::class)
    ->setParam(DbConnection::class);

$user = $container->get(UserModel::class);
```

The order of the parameters doesn't matter , and please note that on ly in case of class parameters we can omit the parameter name. 

```
$container->set(UserModel::class)
    ->setParam('conn', DbConnection::class)
    ->setParam('mailer', MailerService::class);
```

But as we can see we passed the parameters ourselves , but it kinda redundancy :) 

So let's check a more sensible example :

```
// Shape class
class Shape
{
    private $height;
    private $width;
    private $length;

    public function __construct($height, $width, $length = 30)
    {
        $this->height = $height;
        $this->width = $width;
        $this->length = $length;
    }
}

// set in the container
$container = new Container();

$container->set(Shape::class)
    ->setParam('height', 10)
    ->setParam('width', 20);
```
Now each time we request an instance from the `Shape` class , the container will automatically pass these values to the constructor.

And of course , in case of default parameter value , the container will use the default value , if no value was passed for that parameter !

Finally , the `setParam` not only accept primitives , but can accept any valid PHP data type (closures, arrays, objects ...etc) , so all the following examples are valid :

```
$container->set(DoEverything::class)
    ->setParam('my_function', fn() => true)
    ->setParam('an_array', ['a', 'b', 'c'])
    ->setParam('error', (new \Exception()))
    ->setParam('count', 100);

// UserModel example
$container->set(UserModel::class)
    ->setParam('conn', function() {
        return new DbConnection();
    })
    ->setParam('mailer', function() {
        return new MailerService();
    });
```

### Definitions

Defining dependencies using `set` method is default method to register your dependencies in the container , but it's not the only way. The container support defining dependencies in an array form , using the container's constructor.

So instead of writing the following :

```
$container = new Container();

$container->set(MailerService::class);
$container->set(DbConnection::class);
$container->set(UserModel::class);
```

We could use the array definition method :

```
$container = new Container([
    MailerService::class,
    DbConnection::class,
    UserModel::class
]);
```
The result will be the same , and without using any `set` methods , but how about parameters and setter methods ??

The array definitions also support this functionality , using associative arrays. We have 2 options with associative arrays definitions , the simple method , which is just binding definition to an id :

```
$container = new Container([
    MyServiceInterface::class => MyService::class,
]);
```

And the full array , which includes the definition , params and the setter methods :

```
$container = new Container([
    MyServiceInterface::class => [
        'definition' => MyService::class,
        'params' => [
            'paramA' => (new ServiceA::class),
            'paramB' => (new ServiceB::class),
        ],
        'methods' => [
            'setDbProvider' => [
                'dbName' => 'test'
            ]
        ]
    ],
]);
```

So we have the `definition` key which is mandatory , then we have 2 optional keys `params` and `methods` , and they are the equivalent to the `setParam` and `setMethod`. and here's another example :

```
// Shape class
class Shape
{
    private $height;
    private $width;
    private $length;

    public function __construct($height, $width, $length = 30)
    {
        $this->height = $height;
        $this->width = $width;
        $this->length = $length;
    }
    
    public function setDrawHandler(DrawHandler $handler)
    {
        $this->height = $height;
        $this->width = $width;
        $this->length = $length;
    }
}

// bind Shape class to the container
$container = new Container([
    DrawHandler::class => DrawHandler::class
    Shape::class => [
        'definition' => Shape::class,
        'params' => [
            'height' => 10,
            'width'  => 20
            'length' => 30
        ],
        'methods' => [
            'setDrawHandler' => []
        ]
    ])
]);
```
Since `setDrawHandler` only accepts an instance of the `DrawHandler` class , we don't need to pass the arguments , and the container will resolve the dependencies automatically.

And of course the array definitions support all PHP types , we can pass arrays , closures , objects and we can mix all these cool stuff to create complex definitions :

```
$container = new Container([
    MailerService::class => function() {
        return (new MailerService());
    },
    DbConnection::class => (new DbConnection())),
    UserModel::class => [
        'definition' => UserModel::class,
        'params' => [
            'conn' => DbConnection::class,
            'mailer => MailerService::class
        ],
        'methods' => [
            'setMailer' => [
                'mailer' => MailerExample::class,
            ],
            'setDefaultUserInfo' => [
                'name' => 'test',
                'email' => 'test@example.com'
            ],
        ]
    ]
]);
```

Finally , assuming you have a complex definitions array , and you are using multiple containers in your app , you could save the array in a separated file. Then whenever you want to create a new of instance of the container , you just need to require that file :

```
// /path/to/definitions.php
<?php

return [
    MyServiceInterface::class => [
        'definition' => MyService::class,
        'params' => [
            'paramA' => (new ServiceA::class),
            'paramB' => (new ServiceB::class),
        ],
        'methods' => [
            'setDbProvider' => [
                'dbName' => 'test'
            ]
        ]
    ],
];

// and then somewhere in your application 

<?php

require 'vendor/autoload.php';

use SigmaPHP\Container\Container;

$definitions = require_once(__DIR__ . '/path/to/definitions.php'); 

$container = new Container($definitions);
```

### Shared instances

By default all instances defined by the container are shared , which means cached in the container , so instead of go through all the definitions and a create new instance for each dependency. the container caches all the dependencies , this mechanism add huge performance boost , specially with nested complex dependencies.

So whenever you call the `get` method the same instance will be returned every time !

```
$container = new Container();

$container->set(MailerService::class);

$mailer1 = $container->get(MailerService::class);
$mailer2 = $container->get(MailerService::class);

var_dump($mailer1 === $mailer2) // true
```

So assume that we want to get a new instance out from the container , we could use the `make` method , unlike `get` it will return a fresh instance every time.

```
make($id): object
```

So let's the previous example using `make` :

```
$container = new Container();

$container->set(MailerService::class);

$mailer1 = $container->make(MailerService::class);
$mailer2 = $container->make(MailerService::class);

var_dump($mailer1 === $mailer2) // false
```

But keep in mind that `make` unlike `get` only works with classes , in order to create new instance , so primitives , arrays , closures ...etc , all don't work with `make` ! 

### Factories

In some cases we might have a complex class , which can't be simply resolved by the container , that's where the container introduce factories. A factory is just a closure , which can be executed by the container.

let's check the following example :

```
$container = new Container();

$container->set('create_cube', function () {
    $height = 5;
    $width  = 5;
    $length = 5;

    return (new Shape($height, $width, $length));
});

var_dump($container->get('create_cube')); // Shape
```
Now every time we call the `create_cube` definition, the container will resolve the closure and return a new instance of `Shape` class.

Factories have some cool features , including :

**1- Can access current container's instance**

```
$container = new Container();

$container->set(MailerService::class);
$container->set(DbConnection::class);

$container->set('create_user', function (Container $container) {
    $conn = $container->get(DbConnection::class);
    $mailer = $container->get(MailerService::class);

    return (new UserModel($conn, $mailer));
});
```

So whenever we pass a parameter of type `Container` to our factory , the container will automatically will inject the current container's instance to the factory.

**2- Resolve dependencies for parameters**

So the previous example could be re-written as :

```
$container = new Container();

$container->set(MailerService::class);
$container->set(DbConnection::class);

$container->set('create_user', function (DbConnection $conn, MailerService $mailer) {
    return (new UserModel($conn, $mailer));
});
```
**3- Work with `setParam`**

So we could easily bind primitive parameters to our factory :

```
$container = new Container();

$container->set('db_connection', function ($host, $db, $user, $pass) {
    return (new \PDO("mysql:host=$host;dbname=$db", $user, $pass));
})
    ->setParam('host', 'localhost')
    ->setParam('db', 'test')
    ->setParam('user', 'root')
    ->setParam('pass', 'root');

var_dump($container->get('db_connection')); // PDO
```

### Call a method in class 

In some cases we might need to call a method in a class , without instantiate an instance form the class. Here comes the `call` method , a function provided by the container , so we can call methods in classes directly.

```
call(string $id, string $method, array $args = []): mixed
```
First we pass the `$id` which is the class name , registered in the container , then `$method` the name of the method we desire to call , finally we have an optional parameter `$args` to bind any arguments to the method.

```
class Calculator
{
    public function add($a, $b)
    {
        return $a + $b;
    }
}

// somewhere in the app
$container = new Container();

$container->set(Calculator::class);

$result = $container->call(Calculator::class, 'add', [
    'a' => 5,
    'b' => 6,
]);

var_dump($result); // 11
```

And as usual the `call` method support dependency injection by default , without the need to state any parameters :

```
class Notification
{
    public function sendAlert(MailerService $mailer)
    {
        /* the code to send the email... */
    }
}

// somewhere in the app
$container = new Container();

$container->set(Notification::class);
$container->set(MailerService::class);

$container->call(Notification::class, 'sendAlert');
```
The `sendAlert` method will receive an instance from the `MailerService` class automatically. 

### Call a closure 

Similar to call method in class , the container can also call closures on fly , without registering them in the container. And still all dependencies will be injected to the closure.

```
callFunction(\Closure $closure, array $args = []): mixed
```
The `callFunction` method accepts 2 parameters , the first one is the closure , and the second is an optional array of parameters.

```
$container = new Container();

$result = $container->callFunction(function ($a, $b) {
        return $a + $b;
    }, 
    [
        'a' => 5,
        'b' => 6,
    ]
);

var_dump($result); // 11
```

And here's another example with dependency injection :

```
$container = new Container();

$container->set(DbConnection::class);
$container->set(MailerService::class);

$container->callFunction(function (DbConnection $conn, MailerService $mailer) {
    /* fetch data and send emails for example */
});
```

### Service providers 

One of the best features provided by the container , is the service providers. Using the providers your applications will gain huge boost specially in the extendability.

So far in all previous examples in this documentation we have been setting the dependencies one by one using `set` method , or definitions array , however with service providers we can turn our application to a group of plugins (extensions). that could be easily added or removed depending on the requirements.

Another cool features about service providers , that it will allow other developer to contribute to our application , by developing packages and register them in the container using service providers.

To write your own service providers , we start by implementing the `ServiceProviderInterface` :

```
<?php

namespace SigmaPHP\Container\Interfaces;

use SigmaPHP\Container\Container;

/**
 * Service Provider Interface
 */
interface ServiceProviderInterface
{
    /**
     * The boot method , will be called after all 
     * dependencies were defined in the container.
     * 
     * @param Container $container
     * @return void
     */
    public function boot(Container $container);

    /**
     * Add a definition to the container.
     * 
     * @param Container $container
     * @return void
     */
    public function register(Container $container);
}
```
The `ServiceProviderInterface`will require 2 methods to be implemented in our service provider :

* `register(Container $container): void` : the `register` method has access to the current instant of the container , and we use this method to register our dependencies that don't perform any operations or depend any other functionality from another dependency. 

* `boot(Container $container): void` : the `boot` method is identical to the `register` method , but will main difference which is `boot` will be executed once all services are registered.

Simply put , if the service provider will just register some classes using `set` , then we use `register` , since it's more suitable for plain registering. On the other side assume we need to fetch some data from the database before creating an instance from the class , or we need to write some logs in a file , in this case we use the `boot` since we will be sure that all other services have been registered , and we can access them.

Let's have some examples :

```
class UserServiceProvider implements ServiceProviderInterface
{
    public function boot(Container $container) {}

    public function register(Container $container)
    {
        $container->set(UserModel::class);
    }
}

class MailServiceProvider implements ServiceProviderInterface
{
    public function boot(Container $container) {}

    public function register(Container $container)
    {
        $container->set(MailerService::class);
    }
}

class DbConnectionServiceProvider implements ServiceProviderInterface
{
    public function boot(Container $container) {}

    public function register(Container $container)
    {
        $container->set(DbConnection::class);
    }
}
```
So we created 3 separated service providers for each of our dependencies , and since these we don't use any other services when registering these classes , we used the `register` method.

```
class DashboardServiceProvider implements ServiceProviderInterface
{
    public function boot(Container $container) {
        $db = $container->get(DbConnection::class);

        $query = $db->query("SELECT * FROM admins WHERE super_admin = TRUE");

        $admin = $query->fetch()[0];

        $container->set(Dashboard::class)
            ->setParam('admin', $admin);
    }

    public function register(Container $container) {}
}
```

As we can notice in the example above , in order to insatiate a new instance form the `Dashboard` class , we need to pass the admin information that we just retrieved from the database. Using `register` method we have no guarantee that `DbConnection` was registered yet , in similar situations register our dependencies using `boot` method to make sure that all dependencies were registered and ready to use.

So now we have our providers , how can we register them ??

The answer is simply by using the `registerProvider` method

```
registerProvider(string $provider): void
```
The `registerProvider` method only require the provider's path.

```
$container = new Container();

$container->registerProvider(MailerServiceProvider::class);
$container->registerProvider(DbConnectionProvider::class);
$container->registerProvider(UserProvider::class);

$user = $container->get(UserModel::class);
```
Finally in case we have many providers , instead of registering them one by one , we can use the `registerProviders` method , which accept an array of providers , so we can register all of them at once.

```
registerProviders(array $providers): void
```
So we can rewrite the previous example as following :

```
$container = new Container();

$container->registerProviders([
    MailerServiceProvider::class,
    DbConnectionProvider::class,
    UserProvider::class]
);

$user = $container->get(UserModel::class);
```

### Autowiring 

So far we have been registering all of our dependencies manually using variety of methods. Instead of registering your dependencies manually , the container provides one elegant function : `autowire` to register your dependencies automatically.

But what is the autowiring ??

Autowiring is mechanism which is used by dependency injection containers to autoload the requested dependency.

By default autowiring is disabled , to enable autowiring , all what you have to do , is to call `autowire` method once you created the container.

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Container\Container;

$container = new Container();

$container->autowire();

$user = $container->get(UserModel::class);
```
The autowiring feature in SigmaPHP-Container is clever , fast and almost has 0 effect on the performance , but unfortunately it comes with 2 major downsides :

1- Autowiring only works with constructor injection , so if you have some setter injection in your classes , those won't be resolved !

2- Autowiring can only resolve class based dependencies  , so if your class's constructor accepts primitive parameters. The container will throw an exception !

So in order to mitigate these 2 obstacles , you can register classes with these kind of requirements using `set` method , definitions array or service providers.

## License
(SigmaPHP-Container) released under the terms of the MIT license.
