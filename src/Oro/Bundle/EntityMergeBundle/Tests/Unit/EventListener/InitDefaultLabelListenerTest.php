<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\InitDefaultLabelListener;

class InitDefaultLabelListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Namespace\Entity';
    const RELATED_ENTITY = 'Namespace\Related';

    /**
     * @var InitDefaultLabelListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mergeConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldMetadata;

    protected function setUp()
    {
        $this->entityMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mergeConfigProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata
            ->expects($this->any())
            ->method('getFieldsMetadata')
            ->will($this->returnValue([$this->fieldMetadata]));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('getEntityMetadata')
            ->will($this->returnValue($this->entityMetadata));

        $this->config = $this
            ->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue('label'));

        $this->mergeConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $this->listener = new InitDefaultLabelListener($this->mergeConfigProvider);
    }

    public function testOnCreateMetadata()
    {

        $this->fieldMetadata
            ->expects($this->never())
            ->method('set');

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onCreateMetadata($event);
    }

    public function testOnCreateMetadataWithLabel()
    {
        $this->fieldMetadata
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('hasDoctrineMetadata')
            ->will($this->returnValue(true));

        $doctrineMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineMetadata
            ->expects($this->any())
            ->method('isMappedBySourceEntity')
            ->will($this->returnValue(true));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('fieldName'));

        $this->entityMetadata
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('Namespace\Entity'));

        $this->mergeConfigProvider
            ->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));

        $this->fieldMetadata
            ->expects($this->once())
            ->method('set');

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onCreateMetadata($event);
    }

    public function testOnCreateMetadataWithLabelBySourceEntity()
    {
        $this->fieldMetadata
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('isCollection')
            ->will($this->returnValue(true));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('hasDoctrineMetadata')
            ->will($this->returnValue(true));

        $doctrineMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineMetadata
            ->expects($this->any())
            ->method('isMappedBySourceEntity')
            ->will($this->returnValue(false));

        $doctrineMetadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(self::RELATED_ENTITY));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $this->fieldMetadata
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('fieldName'));

        $this->entityMetadata
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('Namespace\Entity'));

        $this->mergeConfigProvider
            ->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));

        $this->fieldMetadata
            ->expects($this->once())
            ->method('set');

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onCreateMetadata($event);
    }
}
