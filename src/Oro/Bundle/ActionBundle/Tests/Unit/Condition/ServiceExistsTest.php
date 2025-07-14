<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Condition;

use Oro\Bundle\ActionBundle\Condition\ServiceExists;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ServiceExistsTest extends TestCase
{
    private const PROPERTY_PATH_NAME = 'testPropertyPath';

    private ContainerInterface&MockObject $container;
    private PropertyPathInterface $propertyPath;
    private ServiceExists $serviceExists;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->propertyPath = $this->createMock(PropertyPathInterface::class);
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->willReturn(self::PROPERTY_PATH_NAME);
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->willReturn([self::PROPERTY_PATH_NAME]);

        $this->serviceExists = new ServiceExists($this->container);
    }

    public function testGetName(): void
    {
        $this->assertEquals('service_exists', $this->serviceExists->getName());
    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf(ServiceExists::class, $this->serviceExists->initialize([$this->propertyPath]));
    }

    public function testToArray(): void
    {
        $result = $this->serviceExists->initialize([$this->propertyPath])->toArray();

        $this->assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@service_exists']['parameters'][0]);
    }

    public function testCompile(): void
    {
        $result = $this->serviceExists->compile('$factoryAccessor');

        self::assertStringContainsString('$factoryAccessor->create(\'service_exists\'', $result);
    }

    public function testSetContextAccessor(): void
    {
        $contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->serviceExists->setContextAccessor($contextAccessor);

        $this->assertInstanceOf(
            get_class($contextAccessor),
            ReflectionUtil::getPropertyValue($this->serviceExists, 'contextAccessor')
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testEvaluates(bool $hasService, bool $expected): void
    {
        $this->container->expects($this->any())
            ->method('has')
            ->willReturn($hasService);

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturn('oro_bundle.service');

        $this->serviceExists->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        $this->assertEquals(
            $expected,
            $this->serviceExists->evaluate('oro_bundle.service')
        );
    }

    public function dataProvider(): array
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
