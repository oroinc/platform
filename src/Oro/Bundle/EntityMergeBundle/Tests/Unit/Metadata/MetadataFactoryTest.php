<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class MetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY         = 'Namespace\EntityName';
    const RELATED_ENTITY = 'Namespace\RelatedEntity';

    /**
     * @var MetadataFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldId;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    public function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this
            ->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->config = $this
            ->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineMetadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(
                [
                    'getName',
                    'getAssociationsByTargetClass',
                    'getAssociationMapping',
                    'getFieldMapping',
                    'hasAssociation'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldId = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config
            ->expects($this->any())
            ->method('all')
            ->will($this->returnValue(['option' => true]));

        $this->config
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($this->fieldId));

        $this->doctrineHelper->expects($this->any())
            ->method('getDoctrineMetadataFor')
            ->will($this->returnValue($this->doctrineMetadata));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValue($this->repository));

        $this->factory = new MetadataFactory(
            $this->configProvider,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Merge config for "Namespace\RelatedEntity" is not exist.
     */
    public function testCreateMergeMetadataEmpty()
    {
        $metadata = $this->factory->createMergeMetadata(self::RELATED_ENTITY);
        $this->assertNull($metadata);
    }

    public function testCreateMergeMetadata()
    {
        $this->configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $metadata = $this->factory->createMergeMetadata(self::ENTITY);
        $this->assertNotNull($metadata);
        $this->assertInstanceOf(
            '\Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata',
            $metadata
        );
    }

    public function testCreateFieldsMetadataEmpty()
    {
        $metadata = $this->factory->createFieldsMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertEmpty($metadata);
    }

    public function testCreateFieldsMetadataReturnFieldMetadata()
    {
        $this->configProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$this->config, $this->config]));

        $this->fieldId
            ->expects($this->any())
            ->method('getFieldType')
            ->will($this->returnValue('string'));

        $this->fieldId
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('string'));

        $this->doctrineMetadata
            ->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(['ref-one' => []]));

        $this->factory = new MetadataFactory(
            $this->configProvider,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
        $metadata      = $this->factory->createFieldsMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);

        $this->assertInstanceOf(
            'Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata',
            $metadata['string']
        );
    }

    public function testCreateFieldsMetadataReturnCollectionMetadata()
    {
        $this->configProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$this->config, $this->config]));

        $this->fieldId
            ->expects($this->any())
            ->method('getFieldType')
            ->will($this->returnValue('ref-one'));

        $this->fieldId
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('ref-one'));

        $this->doctrineMetadata
            ->expects($this->any())
            ->method('hasAssociation')
            ->will($this->returnValue(true));

        $this->doctrineMetadata
            ->expects($this->any())
            ->method('getAssociationMapping')
            ->will($this->returnValue(['ref-one' => []]));

        $metadata = $this->factory->createFieldsMetadata(self::ENTITY);

        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);
        $this->assertInstanceOf(
            'Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata',
            $metadata['ref-one']
        );
    }

    public function testCreateMappedOutsideFieldsMetadataByConfigEmpty()
    {
        $metadata = $this->factory
            ->createMappedOutsideFieldsMetadataByConfig(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertEmpty($metadata);
    }

    public function testCreateMappedOutsideFieldsMetadataByConfig()
    {
        $config = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue')
            ->getMock();

        $configField = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->getMock();

        $configEntity = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->getMock();

        $configEntity
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(self::RELATED_ENTITY));

        $configField
            ->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('fieldName'));

        $configField
            ->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($configEntity));

        $config
            ->expects($this->once())
            ->method('getField')
            ->will($this->returnValue($configField));

        $this->doctrineMetadata
            ->expects($this->any())
            ->method('hasAssociation')
            ->will($this->returnValue(true));

        $this->doctrineMetadata
            ->expects($this->any())
            ->method('getAssociationMapping')
            ->with('fieldName')
            ->will($this->returnValue(['targetEntity' => self::ENTITY]));

        $this->configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $this->repository
            ->expects($this->once())
            ->method('findBy')
            ->will($this->returnValue([$config]));

        $metadata = $this->factory
            ->createMappedOutsideFieldsMetadataByConfig(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);
    }

    public function testCreateMappedOutsideFieldsMetadataByDoctrineMetadataEmpty()
    {
        $metadata = $this->factory
            ->createMappedOutsideFieldsMetadataByDoctrineMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertEmpty($metadata);
    }

    public function testCreateMappedOutsideFieldsMetadataByDoctrineMetadata()
    {
        $this->doctrineMetadata
            ->expects($this->any())
            ->method('getAssociationsByTargetClass')
            ->will(
                $this->returnValue(
                    [
                        'ref-one' => [
                            'fieldName' => 'field'
                        ]
                    ]
                )
            );

        $this->doctrineMetadata
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(self::RELATED_ENTITY));

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getAllMetadata')
            ->will($this->returnValue([$this->doctrineMetadata]));

        $metadata = $this->factory
            ->createMappedOutsideFieldsMetadataByDoctrineMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);
    }
}
