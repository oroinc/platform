<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Condition;

use Oro\Bundle\ActionBundle\Condition\ServiceExists;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ServiceExistsTest extends \PHPUnit\Framework\TestCase
{
    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var ServiceExists
     */
    protected $serviceExists;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->propertyPath = $this->getMockBuilder(PropertyPathInterface::class)
            ->getMock();
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $this->serviceExists = new ServiceExists($this->container);
    }

    public function testGetName()
    {
        $this->assertEquals('service_exists', $this->serviceExists->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(ServiceExists::class, $this->serviceExists->initialize([$this->propertyPath]));
    }

    public function testToArray()
    {
        $result = $this->serviceExists->initialize([$this->propertyPath])->toArray();

        $this->assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@service_exists']['parameters'][0]);
    }

    public function testCompile()
    {
        $result = $this->serviceExists->compile('$factoryAccessor');

        $this->assertContains('$factoryAccessor->create(\'service_exists\'', $result);
    }

    public function testSetContextAccessor()
    {
        /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceExists->setContextAccessor($contextAccessor);

        $reflection = new \ReflectionProperty(get_class($this->serviceExists), 'contextAccessor');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(get_class($contextAccessor), $reflection->getValue($this->serviceExists));
    }



    /**
     * @dataProvider dataProvider
     * @param bool $hasService
     * @param bool $expected
     */
    public function testEvaluates($hasService, $expected)
    {
        $this->container->expects($this->any())
            ->method('has')
            ->willReturn($hasService);

        /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('oro_bundle.service'));

        $this->serviceExists->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        $this->assertEquals(
            $expected,
            $this->serviceExists->evaluate('oro_bundle.service')
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'route exists' => [
                'hasService' => true,
                'expected' => true,
            ],
            'route not exists' => [
                'hasService' => false,
                'expected' => false,
            ],
        ];
    }
}
