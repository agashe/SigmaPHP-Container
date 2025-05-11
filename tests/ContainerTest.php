<?php

use PHPUnit\Framework\TestCase;
use SigmaPHP\Container\Container;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\NotFoundException;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\MailerInterface as MailerExampleInterface;
use SigmaPHP\Container\Tests\Examples\MarketingMailer as MarketingMailerExample;
use SigmaPHP\Container\Tests\Examples\Greeter as GreeterExample;
use SigmaPHP\Container\Tests\Examples\Box as BoxExample;
use SigmaPHP\Container\Tests\Examples\User as UserExample;
use SigmaPHP\Container\Tests\Examples\Admin as AdminExample;
use SigmaPHP\Container\Tests\Examples\Customer as CustomerExample;
use SigmaPHP\Container\Tests\Examples\SuperAdmin as SuperAdminExample;
use SigmaPHP\Container\Tests\Examples\MarketingAdmin as MarketingAdminExample;
use SigmaPHP\Container\Tests\Examples\Notification as NotificationExample;
use SigmaPHP\Container\Tests\Examples\Log as LogExample;
use SigmaPHP\Container\Tests\Examples\ErrorHandler as ErrorHandlerExample;
use SigmaPHP\Container\Tests\Examples\MailerServiceProvider
    as MailerExampleProvider;
use SigmaPHP\Container\Tests\Examples\InvalidServiceProvider
    as InvalidServiceProviderExample;
use SigmaPHP\Container\Tests\Examples\UserServiceProvider
    as UserExampleProvider;
use SigmaPHP\Container\Tests\Examples\LogServiceProvider
    as LogExampleProvider;

/**
 * Container Test
 */
class ContainerTest extends TestCase
{
    /**
     * @var Container $this->container
     */
    protected $container;

    /**
     * ContainerTest SetUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    /**
    * Get value of private property.
    *
    * @param mixed $object
    * @param string $property
    * @return mixed
    */
    private function getPrivatePropertyValue($object, $property)
    {
        $objectReflection = new \ReflectionClass($object);
        $propertyReflection = $objectReflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }

    /**
     * Test container can save definitions.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanSaveDefinitions()
    {
        $this->container->set('mailer', MailerExample::class);

        // get private dependencies array
        $dependencies = $this->getPrivatePropertyValue(
            $this->container,
            'dependencies'
        );

        $this->assertEquals(MailerExample::class, $dependencies['mailer']);
    }

    /**
     * Test container can save definitions with single parameter.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanSaveDefinitionsWithSingleParameter()
    {
        $this->container->set(MailerExample::class);

        // get private dependencies array
        $dependencies = $this->getPrivatePropertyValue(
            $this->container,
            'dependencies'
        );

        $this->assertEquals(
            MailerExample::class,
            $dependencies[MailerExample::class]
        );
    }

    /**
     * Test container can throw exception if the single parameter definition
     * is invalid - not a class path.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfInvalidSingleDefinition()
    {
        $this->expectException(ContainerException::class);

        $this->container->set('invalid');
    }

    /**
     * Test container can save a batch definitions at once.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanSaveABatchDefinitionsAtOnce()
    {
        $this->container->setAll([
            'mailer' => MailerExample::class,
            UserExample::class,
            'log' => LogExample::class
        ]);

        // get private dependencies array
        $dependencies = $this->getPrivatePropertyValue(
            $this->container,
            'dependencies'
        );

        $this->assertEquals(MailerExample::class, $dependencies['mailer']);
        $this->assertEquals(UserExample::class,
            $dependencies[UserExample::class]);
        $this->assertEquals(LogExample::class, $dependencies['log']);
    }

    /**
     * Test container will throw exception for invalid batch definitions.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionForInvalidBatchDefinitions()
    {
        $invalidValues = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
            fn() => true
        ];

        $countInvalidProviders = count($invalidValues);

        foreach ($invalidValues as $invalidValue) {
            try {
                $this->container->setAll($invalidValue);
            } catch (\Exception $e) {
                if ($e instanceof \InvalidArgumentException) {
                    $countInvalidProviders -= 1;
                }
            }
        }

        $this->assertEquals(0, $countInvalidProviders);
    }

    /**
     * Test container can check for id existence.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanCheckForIdExistence()
    {
        $this->container->set('mailer', MailerExample::class);

        $this->assertTrue($this->container->has('mailer'));
    }

    /**
     * Test container can create new instance from a definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanCreateNewInstanceFromADefinition()
    {
        $this->container->set('mailer', MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('mailer')
        );
    }

    /**
     * Test container will return same instance.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillReturnSameInstance()
    {
        $this->container->set('mailer', MailerExample::class);

        $foo = $this->container->get('mailer');
        $bar = $this->container->get('mailer');

        $this->assertTrue($foo === $bar);
    }

    /**
     * 
     * Test container can accept different types of definitions.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptDifferentTypesOfDefinitions()
    {
        $definitions = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
            fn() => true
        ];

        $countAssertions = count($definitions);

        foreach ($definitions as $i => $definition) {
            $this->container->set("item{$i}", $definition);
            
            if ($this->container->get("item{$i}") == $definition) {
                $countAssertions -= 1;
            }
        }

        $this->assertEquals(0, $countAssertions);
    }

    /**
     * Test container can override IDs.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanOverrideIds()
    {
        $this->container->set('my_class', MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('my_class')
        );

        $this->container->set('my_class', LogExample::class);

        $this->assertInstanceOf(
            LogExample::class,
            $this->container->get('my_class')
        );

        $this->container->set('my_class', GreeterExample::class);

        $this->assertInstanceOf(
            GreeterExample::class,
            $this->container->get('my_class')
        );
    }

    /**
     * Test container will throw exception if id is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfIdIsNotFound()
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('mailer');
    }

    /**
     * Test container will throw exception for invalid id.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionForInvalidId()
    {
        $invalidValues = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
            fn() => true
        ];

        $countInvalidIds = count($invalidValues);

        foreach ($invalidValues as $invalidValue) {
            try {
                $this->container->set($invalidValue, MailerExample::class);
            } catch (\Exception $e) {
                if ($e instanceof ContainerException) {
                    $countInvalidIds -= 1;
                }
            }
        }

        $this->assertEquals(0, $countInvalidIds);
    }

    /**
     * Test container can accept class path as id.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptClassPathAsId()
    {
        $this->container->set(MailerExample::class, MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get(MailerExample::class)
        );
    }

    /**
     * Test container can accept interfaces as id.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptInterfacesAsId()
    {
        $this->container->set(
            MailerExampleInterface::class, 
            MailerExample::class
        );

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get(MailerExampleInterface::class)
        );
    }

    /**
     * Test container can accept class path as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptClassPathAsDefinition()
    {
        $this->container->set('mailer', MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('mailer')
        );
    }

    /**
     * Test container can accept factory (closure) as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptFactoryAsDefinition()
    {
        $this->container->set('mailer', function () {
            return new MailerExample();
        });

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('mailer')
        );
    }

    /**
     * Test container can accept arrow functions as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptArrowFunctionsAsDefinition()
    {
        $this->container->set('a_number', (fn() => 101 ));

        $this->assertEquals(101, $this->container->get('a_number'));
    }

    /**
     * Test container can accept object as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptObjectAsDefinition()
    {
        $this->container->set('mailer', (new MailerExample()));

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('mailer')
        );
    }

    /**
     * Test container can accept invocable class as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptInvocableClassAsDefinition()
    {
        $this->container->set(GreeterExample::class, (new GreeterExample()));

        $this->assertInstanceOf(
            GreeterExample::class,
            $this->container->get(GreeterExample::class)
        );

        $greeterService = $this->container->get(GreeterExample::class);
        
        // invoke the class
        $greeterService();

        $this->expectOutputString("Hello SigmaPHP-Container !\n");
    }

    /**
     * Test container can accept anonymous class as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptAnonymousClassAsDefinition()
    {
        $this->container->set('anonymous', (new class () {}));

        $this->assertTrue(is_object($this->container->get('anonymous')));
    }

    /**
     * Test container can define parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanDefineParameters()
    {
        $this->container->set('mailer', MailerExample::class)
            ->setParam('user_name', 'test');

        // get private params array
        $params = $this->getPrivatePropertyValue($this->container, 'params');

        $this->assertEquals('test', $params['mailer']['user_name']);
    }

    /**
     * Test container can define single parameters for classes path.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanDefineSingleParametersForClassesPath()
    {
        $this->container->set(UserExample::class)
            ->setParam(MailerExample::class);

        // get private params array
        $params = $this->getPrivatePropertyValue($this->container, 'params');

        $this->assertEquals(
            MailerExample::class,
            $params[UserExample::class][MailerExample::class]
        );
    }
  
    /**
     * Test container can throw exception if the single parameter for `setParam`
     * is not bounded to a class.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfParameterNotBounded()
    {
        $this->expectException(ContainerException::class);

        $this->container->setParam('invalid');
    }

    /**
     * Test container can throw exception if the single parameter for `setParam`
     * is invalid - not a class path.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfInvalidSingleParameter()
    {
        $this->expectException(ContainerException::class);

        $this->container->set('mailer', MailerExample::class)
            ->setParam('invalid');
    }
    
    /**
     * Test container can throw exception if trying to set a parameter for an
     * invalid item which is not a class path or a closure.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfInvalidParameterBinding()
    {
        $invalidValues = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
        ];

        $countInvalidBindings = count($invalidValues);

        foreach ($invalidValues as $invalidValue) {
            try {
                $this->container->set($invalidValue, MailerExample::class);
            } catch (\Exception $e) {
                if ($e instanceof ContainerException) {
                    $countInvalidBindings -= 1;
                }
            }
        }

        $this->assertEquals(0, $countInvalidBindings);
    }

    /**
     * Test container can bind primitive parameters to definitions.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanBindPrimitiveParametersToDefinitions()
    {
        // parameters order has no effect
        $this->container->set('box', BoxExample::class)
            ->setParam('length', 10)
            ->setParam('height', 30)
            ->setParam('width' , 20);

        $box = $this->container->get('box');

        $height = $this->getPrivatePropertyValue($box, 'height');
        $this->assertEquals(30, $height);

        $width = $this->getPrivatePropertyValue($box, 'width');
        $this->assertEquals(20, $width);

        $length = $this->getPrivatePropertyValue($box, 'length');
        $this->assertEquals(10, $length);
    }

    /**
     * Test primitive parameters default values are working.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testPrimitiveParametersDefaultValuesAreWorking()
    {
        // parameters order has no effect
        $this->container->set('box', BoxExample::class)
            ->setParam('height', 30)
            ->setParam('width' , 20);

        $box = $this->container->get('box');

        $height = $this->getPrivatePropertyValue($box, 'height');
        $this->assertEquals(30, $height);

        $width = $this->getPrivatePropertyValue($box, 'width');
        $this->assertEquals(20, $width);

        $length = $this->getPrivatePropertyValue($box, 'length');
        $this->assertEquals(50, $length);
    }

    /**
     * Test container can inject classes.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectClasses()
    {
        $this->container->set(MailerExample::class, MailerExample::class);
        $this->container->set(UserExample::class, UserExample::class)
            ->setParam(MailerExample::class);

        $this->assertInstanceOf(
            UserExample::class,
            $this->container->get(UserExample::class)
        );

        $user = $this->container->get(UserExample::class);

        $user->name = 'ahmed';
        $user->email = 'ahmed@eample.com';

        $user->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"ahmed\") was sent to : ahmed@eample.com\n"
        );
    }

    /**
     * Test container can inject both classes and primitives.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectClassesAndPrimitives()
    {
        $this->container->set(MailerExample::class, MailerExample::class);
        $this->container->set(AdminExample::class, AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam(MailerExample::class)
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $this->container->get(AdminExample::class)
        );

        $admin = $this->container->get(AdminExample::class);

        $admin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"admin\") was sent to : admin@example.com\n"
        );
    }

    /**
     * Test container can inject classes as a parameter with name.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectClassesAsAParameterWithName()
    {
        $this->container->set(MailerExample::class, MailerExample::class);
        $this->container->set(AdminExample::class, AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam('mailer', MailerExample::class)
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $this->container->get(AdminExample::class)
        );

        $admin = $this->container->get(AdminExample::class);

        $admin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"admin\") was sent to : admin@example.com\n"
        );
    }

    /**
     * Test container can inject objects as parameter.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectObjectsAsParameter()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam('mailer', (new MailerExample()))
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $this->container->get(AdminExample::class)
        );

        $admin = $this->container->get(AdminExample::class);

        $admin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"admin\") was sent to : admin@example.com\n"
        );
    }

    /**
     * Test container can inject factories as parameter.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectFactoriesAsParameter()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam('mailer', function (Container $c) {
                return $c->get(MailerExample::class);
            })
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $this->container->get(AdminExample::class)
        );

        $admin = $this->container->get(AdminExample::class);

        $admin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"admin\") was sent to : admin@example.com\n"
        );
    }

    /**
     * Test container can bind parameters for factories (closures).
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanBindParametersForFactories()
    {
        $this->container->set(MailerExample::class);
        $this->container->set('sendEmail', 
            function (MailerExample $mailer, $email, $body) {
                $mailer->send($email, $body);
            })
            ->setParam(MailerExample::class)
            ->setParam('email', 'test@example.com')
            ->setParam('body', 'Hi, test');

        $this->container->get('sendEmail');

        $this->expectOutputString(
            "The message (Hi, test) was sent to : test@example.com\n"
        );
    }
    
    /**
     * Test container can bind primitives and default values 
     * for factories (closures).
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanBindPrimitivesAndDefaultValuesForFactories()
    {
        $this->container->set('findBoxVolume',
            function ($h, $w, $l = 50) {
                return $h*$w*$l;
            })
            ->setParam('w', 20)
            ->setParam('h', 10);

        $this->assertEquals(10000, $this->container->get('findBoxVolume'));
    }
    
    /**
     * Test factory can accept the container as a parameter.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testFactoryCanAcceptTheContainerAsAParameter()
    {
        $this->container->set('mailer', MailerExample::class);
        $this->container->set('getMailService', function (Container $c) {
            return $c->get('mailer');
        })->setParam('c', $this->container);

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('getMailService')
        );
    }

    /**
     * Test factory can access current container.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testFactoryCanAccessCurrentContainer()
    {
        $this->container->set(MailerExample::class, MailerExample::class);
        $this->container->set(AdminExample::class, AdminExample::class)
            ->setParam('name', 'super_admin')
            ->setParam(MailerExample::class)
            ->setParam('email', 'super_admin@example.com');

        $this->container->set('super_admin', function (Container $c) {
            return $c->get(AdminExample::class);
        });

        $this->assertInstanceOf(
            AdminExample::class,
            $this->container->get('super_admin')
        );

        $superAdmin = $this->container->get('super_admin');

        $superAdmin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"super_admin\") was " .
            "sent to : super_admin@example.com\n"
        );
    }

    /**
     * Test arrow functions factory can access current container.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testArrowFunctionsFactoryCanAccessCurrentContainer()
    {
        $this->container->set(MailerExample::class, MailerExample::class);

        $this->container->set(
            'my_mailer',
            fn(Container $c) => $c->get(MailerExample::class)
        );

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get('my_mailer')
        );
    }

    /**
     * Test container can inject setter method.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectSetterMethod()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(NotificationExample::class)
            ->setMethod('setMailer', [
                'mailer' => MailerExample::class
            ]);

        $this->assertInstanceOf(
            NotificationExample::class,
            $this->container->get(NotificationExample::class)
        );

        $notificationService = $this->container->get(NotificationExample::class);

        $notificationService->pushMessage('ali', 'ali@example.com');

        $this->expectOutputString(
            "The message (Notification to : \"ali\") was " .
            "sent to : ali@example.com\n"
        );
    }
    
    /**
     * Test container can inject setter method without passing parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectSetterMethodWithoutPassingParameters()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(NotificationExample::class)
            ->setMethod('setMailer');

        $this->assertInstanceOf(
            NotificationExample::class,
            $this->container->get(NotificationExample::class)
        );

        $notificationService = $this->container->get(
            NotificationExample::class
        );

        $notificationService->pushMessage('ali', 'ali@example.com');

        $this->expectOutputString(
            "The message (Notification to : \"ali\") was " .
            "sent to : ali@example.com\n"
        );
    }

    /**
     * Test container can inject setter method with primitive parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectSetterMethodWithPrimitiveParameters()
    {
        $this->container->set('admin_name', 'admin1');
        $this->container->set('admin_email', 'admin1@example.com');

        $this->container->set(MailerExample::class);

        $this->container->set(LogExample::class)
            ->setMethod('setMailerAndAdmin', [
                'mailer' => MailerExample::class,
                'name' => $this->container->get('admin_name'),
                'email' => $this->container->get('admin_email')
            ]);

        $this->assertInstanceOf(
            LogExample::class,
            $this->container->get(LogExample::class)
        );

        $logService = $this->container->get(LogExample::class);

        $logService->sendAlert();

        $this->expectOutputString(
            "The message (Alert to : \"admin1\") was " .
            "sent to : admin1@example.com\n"
        );
    }

    /**
     * Test container can inject objects as setter method.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectObjectsAsSetterMethod()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(NotificationExample::class)
            ->setMethod('setMailer', [
                'mailer' => (new MailerExample())
            ]);

        $this->assertInstanceOf(
            NotificationExample::class,
            $this->container->get(NotificationExample::class)
        );

        $notificationService = $this->container->get(
            NotificationExample::class
        );

        $notificationService->pushMessage('ali', 'ali@example.com');

        $this->expectOutputString(
            "The message (Notification to : \"ali\") was " .
            "sent to : ali@example.com\n"
        );
    }

    /**
     * Test container can inject factories as setter method.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectFactoriesAsSetterMethod()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(NotificationExample::class)
            ->setMethod('setMailer', [
                'mailer' => function (Container $c) {
                    return $c->get(MailerExample::class);
                }
            ]);

        $this->assertInstanceOf(
            NotificationExample::class,
            $this->container->get(NotificationExample::class)
        );

        $notificationService = $this->container->get(
            NotificationExample::class
        );

        $notificationService->pushMessage('ali', 'ali@example.com');

        $this->expectOutputString(
            "The message (Notification to : \"ali\") was " .
            "sent to : ali@example.com\n"
        );
    }

    /**
     * Test container can inject method with no arguments.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectMethodWithNoArguments()
    {
        $this->container->set(LogExample::class)
            ->setMethod('defaultParameters');

        $this->assertInstanceOf(
            LogExample::class,
            $this->container->get(LogExample::class)
        );

        $logService = $this->container->get(LogExample::class);

        $logService->sendAlert();

        $this->expectOutputString(
            "The message (Alert to : \"default_admin\") was " .
            "sent to : default_admin@example.com\n"
        );
    }

    /**
     * Test container can add definitions through constructor.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAddDefinitionsThroughConstructor()
    {
        $container = new Container([
            'mailer' => MailerExample::class
        ]);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
        );
    }

    /**
     * Test constructor can add definitions array using only class name.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testConstructorCanAddDefinitionsArrayUsingOnlyClassName()
    {
        $container = new Container([
            MailerExample::class,
            UserExample::class
        ]);

        $this->assertInstanceOf(
            UserExample::class,
            $container->get(UserExample::class)
        );
    }

    /**
     * Test container constructor definitions accept objects.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerConstructorDefinitionsAcceptObjects()
    {
        $container = new Container([
            'mailer' => (new MailerExample())
        ]);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
        );
    }

    /**
     * Test container constructor definitions accept factories (closures).
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerConstructorDefinitionsAcceptFactories()
    {
        $container = new Container([
            'mailer' => function () {
                return new MailerExample();
            }
        ]);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
        );
    }

    /**
     * Test container can bind parameters through constructor.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanBindParametersThroughConstructor()
    {
        $container = new Container([
            MailerExample::class => MailerExample::class,
            'admin' => [
                'definition' => AdminExample::class,
                'params' => [
                    'name' => 'admin',
                    'email' => 'admin@example.com'
                ]
            ]
        ]);

        $this->assertInstanceOf(
            AdminExample::class,
            $container->get('admin')
        );

        $admin = $container->get('admin');

        $admin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"admin\") was " .
            "sent to : admin@example.com\n"
        );
    }

    /**
     * Test container can bind methods through constructor.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanBindMethodsThroughConstructor()
    {
        $container = new Container([
            MailerExample::class => MailerExample::class,
            LogExample::class => [
                'definition' => LogExample::class,
                'methods' => [
                    'setMailerAndAdmin' => [
                        'mailer' => MailerExample::class,
                        'name' => 'admin1',
                        'email' => 'admin1@example.com'
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf(
            LogExample::class,
            $container->get(LogExample::class)
        );

        $logService = $container->get(LogExample::class);

        $logService->sendAlert();

        $this->expectOutputString(
            "The message (Alert to : \"admin1\") was " .
            "sent to : admin1@example.com\n"
        );
    }

    /**
     * Test container can register providers.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanRegisterProviders()
    {
        $this->container->registerProvider(MailerExampleProvider::class);

        // get private providers array
        $providers = $this->getPrivatePropertyValue(
            $this->container, 
            'providers'
        );

        $this->assertTrue(in_array(MailerExampleProvider::class, $providers));
    }
    
    /**
     * Test container can register multiple providers at once.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanRegisterMultipleProvidersAtOnce()
    {
        $this->container->registerProviders([
            MailerExampleProvider::class,
            UserExampleProvider::class,
            LogExampleProvider::class,
        ]);

        // get private providers array
        $providers = $this->getPrivatePropertyValue(
            $this->container, 
            'providers'
        );

        $this->assertTrue(in_array(MailerExampleProvider::class, $providers));
        $this->assertTrue(in_array(UserExampleProvider::class, $providers));
        $this->assertTrue(in_array(LogExampleProvider::class, $providers));
    }

    /**
     * Test container will throw exception for invalid providers.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionForInvalidProviders()
    {
        $invalidValues = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
            fn() => true
        ];

        $countInvalidProviders = count($invalidValues);

        foreach ($invalidValues as $invalidValue) {
            try {
                $this->container->registerProvider($invalidValue);
            } catch (\Exception $e) {
                if ($e instanceof ContainerException) {
                    $countInvalidProviders -= 1;
                }
            }
        }

        $this->assertEquals(0, $countInvalidProviders);
    }

    /**
     * Test container will throw exception for a provider that doesn't
     * implement the \ServiceProviderInterface.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testExceptionForProvidersDoesNotImplementInterface()
    {
        $this->expectException(ContainerException::class);

        $this->container->registerProvider(
            InvalidServiceProviderExample::class
        );
    }

    /**
     * Test container will throw exception if providers was invalid for batch
     * service providers registration.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testExceptionForInvalidProvidersInBatchRegister()
    {
        $invalidValues = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
            fn() => true
        ];

        $countInvalidProviders = count($invalidValues);

        foreach ($invalidValues as $invalidValue) {
            try {
                $this->container->registerProviders($invalidValue);
            } catch (\Exception $e) {
                if ($e instanceof \InvalidArgumentException) {
                    $countInvalidProviders -= 1;
                }
            }
        }

        $this->assertEquals(0, $countInvalidProviders);
    }

    /**
     * Test container can inject dependencies using providers.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectDependenciesUsingProviders()
    {
        $this->container->registerProvider(MailerExampleProvider::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get(MailerExample::class)
        );
    }

    /**
     * Test providers will be booted once registered.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testProvidersWillBeBootedOnceRegistered()
    {
        $this->container->registerProvider(UserExampleProvider::class);

        $this->assertInstanceOf(
            UserExample::class,
            $this->container->get(UserExample::class)
        );

        $this->expectOutputString(
            "The message (Hello \"mohamed\") was " .
            "sent to : mohamed@example.com\n"
        );
    }

    /**
     * Test providers can inject setter method with primitive parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testProvidersCanInjectSetterMethodWithPrimitiveParameters()
    {
        $this->container->set('admin_name', 'admin2');
        $this->container->set('admin_email', 'admin2@example.com');

        $this->container->set(MailerExample::class);
        $this->container->registerProvider(LogExampleProvider::class);

        $this->assertInstanceOf(
            LogExample::class,
            $this->container->get(LogExample::class)
        );

        $this->expectOutputString(
            "The message (Alert to : \"admin2\") was " .
            "sent to : admin2@example.com\n"
        );
    }
    
    /**
     * Test container can inject PHP built in classes.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectPhpBuiltInClasses()
    {
        $this->container->set(ErrorHandlerExample::class)
            ->setParam('e', \Exception::class);

        $this->assertInstanceOf(
            ErrorHandlerExample::class,
            $this->container->get(ErrorHandlerExample::class)
        );

        $this->container->get(ErrorHandlerExample::class)->printErrorMessage();

        $this->expectOutputString("Help !! Exception\n");
    }
    
    /**
     * Test container can make objects out of class paths.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanMakeObjectsOutOfClassPaths()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(UserExample::class)
            ->setParam(MailerExample::class);

        $this->assertInstanceOf(
            UserExample::class,
            $this->container->make(UserExample::class)
        );

        $user = $this->container->make(UserExample::class);

        $user->name = 'ahmed';
        $user->email = 'ahmed@eample.com';

        $user->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"ahmed\") was sent to : ahmed@eample.com\n"
        );
    }
    
    /**
     * Test make will return new instance on every call.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testMakeWillReturnNewInstanceOnEveryCall()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(UserExample::class)
            ->setParam(MailerExample::class);

        $user1 = $this->container->make(UserExample::class);
        $user2 = $this->container->make(UserExample::class);

        $this->assertFalse($user1 === $user2);
    }

    /**
     * Test make can create instances from PHP built in classes.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testMakeCanCreateInstancesFromPhpBuiltInClasses()
    {
        $this->assertInstanceOf(
            \Exception::class,
            $this->container->make(\Exception::class)
        );
    }

    /**
     * Test make will throw exception if id is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testMakeWillThrowExceptionIfIdIsNotFound()
    {
        $this->expectException(NotFoundException::class);

        $this->container->make('mailer');
    }

    /**
     * Test make will throw exception if the id is not a class path.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testMakeWillThrowExceptionIfTheIdIsNotAClassPath()
    {
        $this->expectException(ContainerException::class);

        $this->container->set('foo', fn() => true);

        $this->container->make('foo');
    }
    
    /**
     * Test container can call methods on classes and inject dependencies.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanCallMethodsOnClassesAndInjectDependencies()
    {
        $this->container->set(MailerExample::class);
        $this->container->set(NotificationExample::class);

        $this->container->call(
            NotificationExample::class,
            'pushMessageUsingMailer',
            [
                'name' => 'TESTING'
            ]
        );

        $this->expectOutputString(
            "The message (Notification using mailer to : \"TESTING\") " . 
            "was sent to : testing@example.com\n"
        );
    }
    
    /**
     * Test call will throw exception if the id is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testCallWillThrowExceptionIfTheIdIsNotFound()
    {
        $this->expectException(NotFoundException::class);

        $this->container->call('unknown', 'unknown');
    }

    /**
     * Test call will throw exception if the id is not a class path.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testCallWillThrowExceptionIfTheIdIsNotAClassPath()
    {
        $this->expectException(ContainerException::class);

        $this->container->set('foo', fn() => true);

        $this->container->call('foo', 'bar');
    }

    /**
     * Test container can call closures and inject dependencies.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanCallClosuresAndInjectDependencies()
    {
        $this->container->set(MailerExample::class);
        
        $this->container->callFunction(
            function (
                MailerExample $mailer, 
                $body,
                $email = 'testing@example.com'
            ) {
                $mailer->send($email, $body);
            },
            [
                'body' => 'Test call function method'
            ]
        );

        $this->expectOutputString(
            "The message (Test call function method) " . 
            "was sent to : testing@example.com\n"
        );
    }

    /**
     * Test call function will throw exception for non closure parameter.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testCallFunctionWillThrowExceptionForNonClosureParameter()
    {
        $this->expectException(ContainerException::class);

        $this->container->set(MailerExample::class);

        $this->container->callFunction(MailerExample::class);
    }
    
    /**
     * Test container can autowire dependencies.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAutowireDependencies()
    {
        $this->container->autowire();

        $this->assertInstanceOf(
            MailerExample::class,
            $this->container->get(MailerExample::class)
        );
        
        $this->assertInstanceOf(
            UserExample::class,
            $this->container->get(UserExample::class)
        );
        
        $this->assertInstanceOf(
            LogExample::class,
            $this->container->get(LogExample::class)
        );

        $this->assertInstanceOf(
            GreeterExample::class,
            $this->container->get(GreeterExample::class)
        );

        $this->assertInstanceOf(
            NotificationExample::class,
            $this->container->get(NotificationExample::class)
        );

        // PHP built-in classes
        $this->assertInstanceOf(
            \Exception::class,
            $this->container->get(\Exception::class)
        );

        // Other classes installed by composer
        $this->assertInstanceOf(
            \SebastianBergmann\LinesOfCode\Counter::class,
            $this->container->get(\SebastianBergmann\LinesOfCode\Counter::class)
        );
    }

    /**
     * Test container will throw exception for autowire primitives parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAutowireWillThrowExceptionForPrimitivesParameters()
    {
        $this->expectException(\ArgumentCountError::class);

        $this->container->autowire();

        $this->assertInstanceOf(
            BoxExample::class,
            $this->container->get(BoxExample::class)
        );
    }

    /**
     * Test autowire can inject interfaces.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAutowireCanInjectInterfaces()
    {
        $this->container->set(
            MailerExampleInterface::class, 
            MarketingMailerExample::class
        );

        $this->container->autowire();

        $this->assertInstanceOf(
            CustomerExample::class,
            $this->container->get(CustomerExample::class)
        );
    }
    
    /**
     * Test autowire can resolve union types.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAutowireCanResolveUnionTypes()
    {
        $this->container->autowire();

        $this->assertInstanceOf(
            SuperAdminExample::class,
            $this->container->get(SuperAdminExample::class)
        );
    }
    
    /**
     * Test autowire can resolve intersection types.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAutowireCanResolveIntersectionTypes()
    {
        $this->container->autowire();

        $this->assertInstanceOf(
            MarketingAdminExample::class,
            $this->container->get(MarketingAdminExample::class)
        );
    }

    /**
     * Test autowire can call methods on classes and inject dependencies.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAutowireCanCallMethodsOnClassesAndInjectDependencies()
    {
        $this->container->autowire();
        
        $this->container->call(
            NotificationExample::class,
            'pushMessageUsingMailer',
            [
                'name' => 'TESTING'
            ]
        );

        $this->expectOutputString(
            "The message (Notification using mailer to : \"TESTING\") " . 
            "was sent to : testing@example.com\n"
        );
    }
}