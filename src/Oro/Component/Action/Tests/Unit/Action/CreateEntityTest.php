<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\CreateEntity;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateEntityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateEntity
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new CreateEntity($this->contextAccessor, $this->registry);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->contextAccessor, $this->registry, $this->action);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options)
    {
        $em = $this->getMockBuilder('\Doctrine\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf($options[CreateEntity::OPTION_KEY_CLASS]));

        if (!empty($options[CreateEntity::OPTION_KEY_FLUSH])) {
            $em->expects($this->once())
                ->method('flush')
                ->with($this->isInstanceOf($options[CreateEntity::OPTION_KEY_CLASS]));
        } else {
            $em->expects($this->never())->method('flush');
        }

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($options[CreateEntity::OPTION_KEY_CLASS])
            ->will($this->returnValue($em));

        $context = new ItemStub(array());
        $attributeName = (string)$options[CreateEntity::OPTION_KEY_ATTRIBUTE];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->$attributeName);
        $this->assertInstanceOf($options[CreateEntity::OPTION_KEY_CLASS], $context->$attributeName);

        /** @var ItemStub $entity */
        $entity = $context->$attributeName;
        $expectedData = !empty($options[CreateEntity::OPTION_KEY_DATA]) ?
            $options[CreateEntity::OPTION_KEY_DATA] :
            array();
        $this->assertInstanceOf($options[CreateEntity::OPTION_KEY_CLASS], $entity);
        $this->assertEquals($expectedData, $entity->getData());
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        $stubStorageClass = 'Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub';

        return array(
            'without data' => array(
                'options' => array(
                    CreateEntity::OPTION_KEY_CLASS     => $stubStorageClass,
                    CreateEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                )
            ),
            'with data' => array(
                'options' => array(
                    CreateEntity::OPTION_KEY_CLASS     => $stubStorageClass,
                    CreateEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                    CreateEntity::OPTION_KEY_DATA      => array('key1' => 'value1', 'key2' => 'value2'),
                )
            ),
            'without flush' => array(
                'options' => array(
                    CreateEntity::OPTION_KEY_CLASS     => $stubStorageClass,
                    CreateEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                    CreateEntity::OPTION_KEY_DATA      => array('key1' => 'value1', 'key2' => 'value2'),
                    CreateEntity::OPTION_KEY_FLUSH     => false
                )
            ),
        );
    }

    public function testExecuteEntityNotManageable()
    {
        $this->expectException(\Oro\Component\Action\Exception\NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $options = array(
            CreateEntity::OPTION_KEY_CLASS     => 'stdClass',
            CreateEntity::OPTION_KEY_ATTRIBUTE => $this->getPropertyPath()
        );
        $context = array();
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteCantCreateEntity()
    {
        $this->expectException(\Oro\Component\Action\Exception\ActionException::class);
        $this->expectExceptionMessage("Can't create entity stdClass. Test exception.");

        $em = $this->getMockBuilder('\Doctrine\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function () {
                        throw new \Exception('Test exception.');
                    }
                )
            );

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $options = array(
            CreateEntity::OPTION_KEY_CLASS     => 'stdClass',
            CreateEntity::OPTION_KEY_ATTRIBUTE => $this->getPropertyPath()
        );
        $context = array();
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    protected function getPropertyPath()
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
