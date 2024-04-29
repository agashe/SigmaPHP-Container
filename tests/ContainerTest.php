<?php

use PHPUnit\Framework\TestCase;
use SigmaPHP\Container\Container;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\NotFoundException;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\MailerInterface as MailerExampleInterface;
use SigmaPHP\Container\Tests\Examples\Greeter as GreeterExample;
use SigmaPHP\Container\Tests\Examples\Box as BoxExample;
use SigmaPHP\Container\Tests\Examples\User as UserExample;
use SigmaPHP\Container\Tests\Examples\Admin as AdminExample;
use SigmaPHP\Container\Tests\Examples\Notification as NotificationExample;
use SigmaPHP\Container\Tests\Examples\Log as LogExample;

/**
 * Container Test
 */
class ContainerTest extends TestCase
{
    /**
     * ContainerTest SetUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
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
        $container = new Container();

        $container->set('mailer', MailerExample::class);

        // get private dependencies array
        $dependencies = $this->getPrivatePropertyValue(
            $container, 
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
        $container = new Container();

        $container->set(MailerExample::class);

        // get private dependencies array
        $dependencies = $this->getPrivatePropertyValue(
            $container, 
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

        $container = new Container();

        $container->set('invalid');
    }

    /**
     * Test container can check for id existence.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanCheckForIdExistence()
    {   
        $container = new Container();

        $container->set('mailer', MailerExample::class);

        $this->assertTrue($container->has('mailer'));
    }

    /**
     * Test container can create new instance from a definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanCreateNewInstanceFromADefinition()
    {   
        $container = new Container();

        $container->set('mailer', MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
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
        $container = new Container();

        $container->set('mailer', MailerExample::class);

        $foo = $container->get('mailer');
        $bar = $container->get('mailer');

        $this->assertTrue($foo === $bar);
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

        $container = new Container();
        $container->get('mailer');
    }

    /**
     * Test container will throw exception for invalid id or definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionForInvalidIdOrDefinition()
    {     
        $container = new Container();
        
        $invalidValues = [
            [],
            false,
            null,
            '',
            123,
            new \stdClass(),
            fn() => true
        ];

        $countInvalidIds = $countInvalidDefinitions = count($invalidValues);

        foreach ($invalidValues as
         $invalidValue) {
            try {
                $container->set($invalidValue, MailerExample::class);
            } catch (\Exception $e) {
                if ($e instanceof ContainerException) {
                    $countInvalidIds -= 1;
                }
            }

            try {
                $container->set('mailer', $invalidValue);
            } catch (\Exception $e) {
                if ($e instanceof ContainerException) {
                    $countInvalidDefinitions -= 1;
                }
            }
        }

        $this->assertEquals(0, $countInvalidIds);
        $this->assertEquals(2, $countInvalidDefinitions);
    }

    /**
     * Test container will throw exception if class is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfClassIsNotFound()
    {   
        $this->expectException(ContainerException::class);

        $container = new Container();
        $container->set('unknown', 'This\Class\Does\Not\Exist');
    }

    /**
     * Test container can accept class path as id.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptClassPathAsId()
    {   
        $container = new Container();

        $container->set(MailerExample::class, MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get(MailerExample::class)
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
        $container = new Container();

        $container->set(MailerExampleInterface::class, MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get(MailerExampleInterface::class)
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
        $container = new Container();

        $container->set('mailer', MailerExample::class);

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
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
        $container = new Container();

        $container->set('mailer', function () {
            return new MailerExample();
        });

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
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
        $container = new Container();

        $container->set('a_number', (fn() => 101 ));

        $this->assertEquals(101, $container->get('a_number'));
    }
    
    /**
     * Test container can accept object as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptObjectAsDefinition()
    {   
        $container = new Container();

        $container->set('mailer', (new MailerExample()));

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('mailer')
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
        $container = new Container();

        $container->set(GreeterExample::class, (new GreeterExample()));

        $this->assertInstanceOf(
            GreeterExample::class,
            $container->get(GreeterExample::class)
        );
    }
    
    /**
     * Test container can accept anonymous class as definition.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanAcceptAnonymousClassAsDefinition()
    {   
        $container = new Container();

        $container->set('anonymous', (new class () {}));

        $this->assertTrue(is_object($container->get('anonymous')));
    }
    
    /**
     * Test container can define parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanDefineParameters()
    {   
        $container = new Container();

        $container->set('mailer', MailerExample::class)
            ->setParam('user_name', 'test');

        // get private params array
        $params = $this->getPrivatePropertyValue($container, 'params');

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
        $container = new Container();

        $container->set(UserExample::class)
            ->setParam(MailerExample::class);

        // get private params array
        $params = $this->getPrivatePropertyValue($container, 'params');

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

        $container = new Container();

        $container->setParam('invalid');
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

        $container = new Container();

        $container->set('mailer', MailerExample::class)
            ->setParam('invalid');
    }

    /**
     * Test container can bind primitive parameters to definitions.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanBindPrimitiveParametersToDefinitions()
    {   
        $container = new Container();

        // parameters order has no effect
        $container->set('box', BoxExample::class)
            ->setParam('length', 10)
            ->setParam('height', 30)
            ->setParam('width' , 20);

        $box = $container->get('box');

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
        $container = new Container();

        // parameters order has no effect
        $container->set('box', BoxExample::class)
            ->setParam('height', 30)
            ->setParam('width' , 20);

        $box = $container->get('box');

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
        $container = new Container();

        $container->set(MailerExample::class, MailerExample::class);
        $container->set(UserExample::class, UserExample::class)
            ->setParam(MailerExample::class);

        $this->assertInstanceOf(
            UserExample::class,
            $container->get(UserExample::class)
        );

        $user = $container->get(UserExample::class);
        
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
        $container = new Container();

        $container->set(MailerExample::class, MailerExample::class);
        $container->set(AdminExample::class, AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam(MailerExample::class)
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $container->get(AdminExample::class)
        );

        $admin = $container->get(AdminExample::class);
        
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
        $container = new Container();

        $container->set(MailerExample::class, MailerExample::class);
        $container->set(AdminExample::class, AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam('mailer', MailerExample::class)
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $container->get(AdminExample::class)
        );

        $admin = $container->get(AdminExample::class);
        
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
        $container = new Container();

        $container->set(MailerExample::class);
        $container->set(AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam('mailer', (new MailerExample()))
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $container->get(AdminExample::class)
        );

        $admin = $container->get(AdminExample::class);
        
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
        $container = new Container();

        $container->set(MailerExample::class);
        $container->set(AdminExample::class)
            ->setParam('name', 'admin')
            ->setParam('mailer', function ($c) {
                return $c->get(MailerExample::class);
            })
            ->setParam('email', 'admin@example.com');

        $this->assertInstanceOf(
            AdminExample::class,
            $container->get(AdminExample::class)
        );

        $admin = $container->get(AdminExample::class);
        
        $admin->sendWelcomeMail();

        $this->expectOutputString(
            "The message (Hello \"admin\") was sent to : admin@example.com\n"
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
        $container = new Container();

        $container->set(MailerExample::class, MailerExample::class);
        $container->set(AdminExample::class, AdminExample::class)
            ->setParam('name', 'super_admin')
            ->setParam(MailerExample::class)
            ->setParam('email', 'super_admin@example.com');

        $container->set('super_admin', function ($c) {
            return $c->get(AdminExample::class);
        });

        $this->assertInstanceOf(
            AdminExample::class,
            $container->get('super_admin')
        );

        $superAdmin = $container->get('super_admin');
        
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
        $container = new Container();

        $container->set(MailerExample::class, MailerExample::class);

        $container->set('my_mailer', fn($c) => $c->get(MailerExample::class));

        $this->assertInstanceOf(
            MailerExample::class,
            $container->get('my_mailer')
        );
    }

     /**
     * Test container can set global value.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanSetGlobalValue()
    {   
        $container = new Container();

        $container->setValue('db_name', 'test');

        // get private values array
        $values = $this->getPrivatePropertyValue($container, 'values');

        $this->assertEquals('test', $values['db_name']);
    }
    
    /**
     * Test container can get global value.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanGetGlobalValue()
    {   
        $container = new Container();

        $container->setValue('db_name', 'test');

        $this->assertEquals('test', $container->getValue('db_name'));
    }

    /**
     * Test container will throw exception if global value is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfGlobalValueIsNotFound()
    {   
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->getValue('unknown');
    }
        
    /**
     * Test container can inject setter method.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectSetterMethod()
    {   
        $container = new Container();

        $container->set(MailerExample::class);
        $container->set(NotificationExample::class)
            ->setMethod('setMailer', [
                'mailer' => MailerExample::class
            ]);

        $this->assertInstanceOf(
            NotificationExample::class,
            $container->get(NotificationExample::class)
        );

        $notificationService = $container->get(NotificationExample::class);
        
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
        $container = new Container();

        $container->setValue('admin_name', 'admin1');
        $container->setValue('admin_email', 'admin1@example.com');

        $container->set(MailerExample::class);

        $container->set(LogExample::class)
            ->setMethod('setMailerAndAdmin', [
                'mailer' => MailerExample::class,
                'name' => $container->getValue('admin_name'), 
                'email' => $container->getValue('admin_email')
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
     * Test container can inject objects as setter method.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanInjectObjectsAsSetterMethod()
    {   
        $container = new Container();

        $container->set(MailerExample::class);
        $container->set(NotificationExample::class)
            ->setMethod('setMailer', [
                'mailer' => (new MailerExample())
            ]);

        $this->assertInstanceOf(
            NotificationExample::class,
            $container->get(NotificationExample::class)
        );

        $notificationService = $container->get(NotificationExample::class);
        
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
        $container = new Container();

        $container->set(MailerExample::class);
        $container->set(NotificationExample::class)
            ->setMethod('setMailer', [
                'mailer' => function ($c) {
                    return $c->get(MailerExample::class);
                }
            ]);

        $this->assertInstanceOf(
            NotificationExample::class,
            $container->get(NotificationExample::class)
        );

        $notificationService = $container->get(NotificationExample::class);
        
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
        $container = new Container();

        $container->set(LogExample::class)
            ->setMethod('defaultParameters');

        $this->assertInstanceOf(
            LogExample::class,
            $container->get(LogExample::class)
        );

        $logService = $container->get(LogExample::class);
        
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
}