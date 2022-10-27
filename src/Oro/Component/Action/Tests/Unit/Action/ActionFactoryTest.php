<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_TYPE = 'test_type';
    private const TEST_TYPE_SERVICE = 'test_type_service';
    private const ALLOWED_TYPES = [
        self::TEST_TYPE => self::TEST_TYPE_SERVICE
    ];

    private function getActionFactory(array $arguments = []): ActionFactory
    {
        $defaultArguments = [
            'container' => $this->createMock(ContainerInterface::class),
            'types'     => self::ALLOWED_TYPES
        ];
        $arguments = array_merge($defaultArguments, $arguments);

        return new ActionFactory($arguments['container'], $arguments['types']);
    }

    public function testCreateNoType()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The action type must be defined');

        $factory = $this->getActionFactory();
        $factory->create(null);
    }

    public function testCreateIncorrectType()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No attached service to action type named `unknown_type`');

        $factory = $this->getActionFactory();
        $factory->create('unknown_type');
    }

    public function testCreateIncorrectInterface()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The service `test_type_service` must implement `ActionInterface`');

        $factory = $this->getActionFactory();
        $factory->create(self::TEST_TYPE);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $type, string $id, array $options = [], bool $isCondition = false)
    {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())
            ->method('initialize')
            ->with($options);

        $condition = null;
        if ($isCondition) {
            $condition = $this->createMock(ExpressionInterface::class);
            $action->expects($this->once())
                ->method('setCondition')
                ->with($condition);
        } else {
            $action->expects($this->never())
                ->method('setCondition');
        }

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($action);

        $factory = $this->getActionFactory(['container' => $container]);

        $this->assertEquals($action, $factory->create($type, $options, $condition));
    }

    public function createDataProvider(): array
    {
        return [
            'empty condition' => [
                'type' => self::TEST_TYPE,
                'id'   => self::TEST_TYPE_SERVICE,
            ],
            'existing condition' => [
                'type'        => self::TEST_TYPE,
                'id'          => self::TEST_TYPE_SERVICE,
                'options'     => ['key' => 'value'],
                'isCondition' => true,
            ],
        ];
    }

    public function testGetTypes()
    {
        $types = ['type1' => 'val1', 'type2' => 'val2'];
        $factory = $this->getActionFactory(['types' => $types]);

        $this->assertEquals($types, $factory->getTypes());
    }

    public function testIsTypeExists()
    {
        $factory = $this->getActionFactory();

        $this->assertFalse($factory->isTypeExists('unknown'));
        $this->assertTrue($factory->isTypeExists(self::TEST_TYPE));
    }
}
