<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Action\CloneEntity;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class CloneEntityTest extends \PHPUnit_Framework_TestCase
{
    /** @var CloneEntity */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new CloneEntity($this->contextAccessor, $this->registry);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $options
     */
    public function testExecute(array $options)
    {
        $meta = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn(['id' => 4]);
        $meta->expects($this->once())
            ->method('setIdentifierValues');

        $em = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ClassUtils::getClass($options[CloneEntity::OPTION_KEY_TARGET])));
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($meta));

        if (!empty($options[CloneEntity::OPTION_KEY_FLUSH])) {
            $em->expects($this->once())
                ->method('flush')
                ->with($this->isInstanceOf(ClassUtils::getClass($options[CloneEntity::OPTION_KEY_TARGET])));
        } else {
            $em->expects($this->never())->method('flush');
        }

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($options[CloneEntity::OPTION_KEY_TARGET]))
            ->will($this->returnValue($em));

        $context = new ItemStub(array());
        $attributeName = (string)$options[CloneEntity::OPTION_KEY_ATTRIBUTE];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->$attributeName);
        $this->assertInstanceOf(
            ClassUtils::getClass($options[CloneEntity::OPTION_KEY_TARGET]),
            $context->$attributeName
        );

        /** @var ItemStub $entity */
        $entity = $context->$attributeName;
        $expectedData = !empty($options[CloneEntity::OPTION_KEY_DATA]) ?
            $options[CloneEntity::OPTION_KEY_DATA] :
            array();
        $this->assertEquals($expectedData, $entity->getData());
    }

    protected function tearDown()
    {
        unset($this->contextAccessor, $this->registry, $this->action);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        $stubTarget = new ItemStub();

        return [
            'without data' => [
                'options' => [
                    CloneEntity::OPTION_KEY_TARGET    => $stubTarget,
                    CloneEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                ]
            ],
            'with data' => [
                'options' => [
                    CloneEntity::OPTION_KEY_TARGET     => $stubTarget,
                    CloneEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                    CloneEntity::OPTION_KEY_DATA      => array('key1' => 'value1', 'key2' => 'value2'),
                ]
            ],
            'without flush' => [
                'options' => [
                    CloneEntity::OPTION_KEY_TARGET     => $stubTarget,
                    CloneEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('test_attribute'),
                    CloneEntity::OPTION_KEY_DATA      => array('key1' => 'value1', 'key2' => 'value2'),
                    CloneEntity::OPTION_KEY_FLUSH     => false
                ]
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\NotManageableEntityException
     * @expectedExceptionMessage Entity class "stdClass" is not manageable.
     */
    public function testExecuteEntityNotManageable()
    {
        $options = array(
            CloneEntity::OPTION_KEY_TARGET     => new \stdClass(),
            CloneEntity::OPTION_KEY_ATTRIBUTE => $this->getPropertyPath()
        );
        $context = array();
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\ActionException
     * @expectedExceptionMessage Can't create entity stdClass. Test exception.
     */
    public function testExecuteCantCreateEntity()
    {
        $meta = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn(['id' => 5]);
        $meta->expects($this->once())
            ->method('setIdentifierValues');

        $em = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($meta));
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
            CloneEntity::OPTION_KEY_TARGET    => new \stdClass(),
            CloneEntity::OPTION_KEY_ATTRIBUTE => $this->getPropertyPath()
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
