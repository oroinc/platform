<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Component\Action\Action\CloneEntity;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Translation\TranslatorInterface;

class CloneEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var CloneEntity */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FlashBagInterface
     */
    protected $flashBag;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->flashBag = $this->getMockBuilder(FlashBagInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new CloneEntity(
            $this->contextAccessor,
            $this->registry,
            $this->translator,
            $this->flashBag,
            $this->logger
        );

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

        if (isset($options[CloneEntity::OPTION_KEY_FLUSH]) && false === $options[CloneEntity::OPTION_KEY_FLUSH]) {
            $em->expects($this->never())->method('flush');
        } else {
            $em->expects($this->once())
                ->method('flush')
                ->with($this->isInstanceOf(ClassUtils::getClass($options[CloneEntity::OPTION_KEY_TARGET])));
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

        $this->flashBag->expects($this->once())->method('add');
        $this->logger->expects($this->once())->method('error');

        $options = array(
            CloneEntity::OPTION_KEY_TARGET    => new \stdClass(),
            CloneEntity::OPTION_KEY_ATTRIBUTE => new PropertyPath('[test_attribute]')
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
