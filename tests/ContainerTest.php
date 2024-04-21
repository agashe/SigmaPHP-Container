<?php 

use PHPUnit\Framework\TestCase;
use SigmaPHP\Container\Container;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\IdNotFoundException;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\User as UserExample;

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

        $this->assertTrue($dependencies['mailer'] instanceof MailerExample);
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
            new \StdClass(),
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
}