<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\OptionSetListener;

class OptionSetListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var OptionSetListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new OptionSetListener();
    }

    public function testPostPersistNotThrowAnExceptionIfClassConfigNotExist()
    {
        $expectedClassName = 'test_class'.uniqid();

        $entity = $this->getMock('StdClass', array(), array(), $expectedClassName);
        $entityManager = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($expectedClassName)
            ->will($this->returnValue(false));
        $configProvider->expects($this->never())
            ->method('getConfig');
        $entityManager->expects($this->once())
            ->method('getExtendConfigProvider')
            ->will($this->returnValue($configProvider));
        $event = $this->getEvent($entityManager, $entity);

        $this->listener->postPersist($event);
    }

    public function testPostPersistNotThrowAnErrorIfSchemaRelationsNotExist()
    {
        $expectedClassName = 'test_class'.uniqid();

        $entity = $this->getMock('StdClass', array(), array(), $expectedClassName);
        $entityManager = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($expectedClassName, null)
            ->will($this->returnValue(true));
        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config->expects($this->once())
            ->method('get')
            ->with('schema')
            ->will($this->returnValue(array()));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedClassName, null)
            ->will($this->returnValue($config));
        $entityManager->expects($this->once())
            ->method('getExtendConfigProvider')
            ->will($this->returnValue($configProvider));
        $event = $this->getEvent($entityManager, $entity);

        $this->listener->postPersist($event);
    }

    public function testPostPersist()
    {
        $expectedClassName = 'test_class'.uniqid();
        $entityId = 42;
        $thirdFieldName = 'thirdFieldName';

        $thirdFieldGetter = 'get'.ucfirst($thirdFieldName);

        $entity = $this->getMockBuilder('StdClass')
            ->disableOriginalConstructor()
            ->setMethods(array($thirdFieldGetter, 'getId'))
            ->setMockClassName($expectedClassName)
            ->getMock();
        $entity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($entityId));

        $expectedOptions = array($entityId);

        $entity->expects($this->once())
            ->method($thirdFieldGetter)
            ->will($this->returnValue($expectedOptions));

        $entityManager = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with($entityId);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));
        $entityManager->expects($this->once())->method('persist');

        $fieldName = 'testFieldName';
        $secondFieldName = 'secondTestField';
        $schema = array('relation' => array($fieldName, $secondFieldName, $thirdFieldName));

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config->expects($this->any())
            ->method('get')
            ->with('schema')
            ->will($this->returnValue($schema));

        $idInterface = $this->getIdInterface('optionSet');
        $idInterface->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($expectedClassName));
        $idInterface->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue($thirdFieldName));

        $map = array(
            array($expectedClassName, null, $config),
            array($expectedClassName, $fieldName, $this->getFieldConfig(null, 'notOptionSet')),
            array($expectedClassName, $secondFieldName, $this->getFieldConfig()),
            array($expectedClassName, $thirdFieldName, $this->getFieldConfig($idInterface))
        );

        $configProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValueMap($map));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider->expects($this->once())
            ->method('getConfigManager')
            ->will($this->returnValue($configManager));

        $entityManager->expects($this->once())
            ->method('getExtendConfigProvider')
            ->will($this->returnValue($configProvider));
        $event = $this->getEvent($entityManager, $entity);

        $this->listener->postPersist($event);
    }

    /**
     * @param null|\PHPUnit_Framework_MockObject_MockObject   $idInterface
     * @param string $type
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFieldConfig($idInterface = null, $type = 'optionSet')
    {
        $fieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $idInterface = $idInterface ? $idInterface : $this->getIdInterface($type);
        $fieldConfig->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($idInterface));

        return $fieldConfig;
    }

    /**
     * @param string $type
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getIdInterface($type = 'optionSet')
    {
        $idInterface = $this->getMock('StdClass', array('getFieldType', 'getClassName', 'getFieldName'));
        $idInterface->expects($this->once())
            ->method('getFieldType')
            ->will($this->returnValue($type));
        return $idInterface;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $entityManager
     * @param \PHPUnit_Framework_MockObject_MockObject $entity
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEvent($entityManager, $entity)
    {
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        return $event;
    }
}
