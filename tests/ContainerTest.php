<?php 

use PHPUnit\Framework\TestCase;
use SigmaPHP\Container\Container;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\IdNotFoundException;
use SigmaPHP\Container\Exceptions\ParameterNotFoundException;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\MailerInterface as MailerExampleInterface;
use SigmaPHP\Container\Tests\Examples\Greeter as GreeterExample;
use SigmaPHP\Container\Tests\Examples\Box as BoxExample;
use SigmaPHP\Container\Tests\Examples\User as UserExample;
use SigmaPHP\Container\Tests\Examples\Admin as AdminExample;

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
        $this->expectException(IdNotFoundException::class);

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
     * Test factory can access current container.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testFactoryCanAccessCurrentContainer()
    {   
        // !! Not Implemented Yet !!
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

        $container->setParam('db_name', 'test');

        // get private params array
        $params = $this->getPrivatePropertyValue($container, 'params');

        $this->assertEquals('test', $params['db_name']);
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

        $container->setParam(MailerExample::class);

        // get private params array
        $params = $this->getPrivatePropertyValue($container, 'params');

        $this->assertEquals(
            MailerExample::class,
            $params[MailerExample::class]
        );
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

        $container->setParam('invalid');
    }

    /**
     * Test container can get unbounded param.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerCanGetUnboundedParam()
    {   
        $container = new Container();

        $container->setParam('db_name', 'test');

        $this->assertEquals('test', $container->getParam('db_name'));
    }
    
    /**
     * Test container will throw exception if param is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testContainerWillThrowExceptionIfParamIsNotFound()
    {   
        $this->expectException(ParameterNotFoundException::class);

        $container = new Container();
        $container->getParam('unknown');
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
            "The message Hello \"ahmed\" was sent to ahmed@eample.com\n"
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
            "The message Hello \"admin\" was sent to admin@example.com\n"
        );
    }
}