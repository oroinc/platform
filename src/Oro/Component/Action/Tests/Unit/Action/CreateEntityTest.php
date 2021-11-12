<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Component\Action\Action\CreateEntity;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var CreateEntity */
    private $action;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new CreateEntity(new ContextAccessor(), $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options)
    {
        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf($options[CreateEntity::OPTION_KEY_CLASS]));

        if (!empty($options[CreateEntity::OPTION_KEY_FLUSH])) {
            $em->expects($this->once())
                ->method('flush')
                ->with($this->isInstanceOf($options[CreateEntity::OPTION_KEY_CLASS]));
        } else {
            $em->expects($this->never())
                ->method('flush');
        }

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($options[CreateEntity::OPTION_KEY_CLASS])
            ->willReturn($em);

        $context = new ItemStub([]);
        $attributeName = (string)$options[CreateEntity::OPTION_KEY_ATTRIBUTE];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->{$attributeName});
        $this->assertInstanceOf($options[CreateEntity::OPTION_KEY_CLASS], $context->{$attributeName});

        /** @var ItemStub $entity */
        $entity = $context->{$attributeName};
        $expectedData = !empty($options[CreateEntity::OPTION_KEY_DATA]) ?
            $options[CreateEntity::OPTION_KEY_DATA] :
            [];
        $this->assertInstanceOf($options[CreateEntity::OPTION_KEY_CLASS], $entity);
        $this->assertEquals($expectedData, $entity->getData());
    }

    public function executeDataProvider(): array
    {
        return [
            'without data' => [
                'options' => [
                    CreateEntity::OPTION_KEY_CLASS     => ItemStub::class,
                    CreateEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                ]
            ],
            'with data' => [
                'options' => [
                    CreateEntity::OPTION_KEY_CLASS     => ItemStub::class,
                    CreateEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                    CreateEntity::OPTION_KEY_DATA      => ['key1' => 'value1', 'key2' => 'value2'],
                ]
            ],
            'without flush' => [
                'options' => [
                    CreateEntity::OPTION_KEY_CLASS     => ItemStub::class,
                    CreateEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                    CreateEntity::OPTION_KEY_DATA      => ['key1' => 'value1', 'key2' => 'value2'],
                    CreateEntity::OPTION_KEY_FLUSH     => false
                ]
            ],
        ];
    }

    public function testExecuteEntityNotManageable()
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage(sprintf('Entity class "%s" is not manageable.', \stdClass::class));

        $options = [
            CreateEntity::OPTION_KEY_CLASS     => \stdClass::class,
            CreateEntity::OPTION_KEY_ATTRIBUTE => $this->createMock(PropertyPath::class)
        ];
        $context = [];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteCantCreateEntity()
    {
        $this->expectException(ActionException::class);
        $this->expectExceptionMessage(sprintf("Can't create entity %s. Test exception.", \stdClass::class));

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Test exception.'));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $options = [
            CreateEntity::OPTION_KEY_CLASS     => \stdClass::class,
            CreateEntity::OPTION_KEY_ATTRIBUTE => $this->createMock(PropertyPath::class)
        ];
        $context = [];
        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
