<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class MetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Namespace\EntityName';
    const RELATED_ENTITY = 'Namespace\RelatedEntity';

    /**
     * @var MetadataFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['getMetadataFactory', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(['getSingleIdentifierFieldName0', 'getAssociationMapping', 'getFieldMapping'])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->setMethods(['getMetadataFor'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
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

        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnValue($this->doctrineMetadata));

        $this->entityManager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Config for "Namespace\RelatedEntity" not exists
     *
     */
    public function testCreateMetadataForEntityWithoutConfig()
    {
        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createMergeMetadata(self::RELATED_ENTITY);
        $this->assertNull($metadata);
    }

    public function testCreateMergeMetadata()
    {
        $this->configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createMergeMetadata(self::ENTITY);
        $this->assertNotNull($metadata);
        $this->assertInstanceOf('\Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata' ,$metadata);
    }

    public function testCreateFieldsMetadataEmpty()
    {
        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createFieldsMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertEmpty($metadata);
    }

    public function testCreateFieldsMetadataReturnFieldMetada()
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

        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createFieldsMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);
        $this->assertInstanceOf('\Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata', $metadata[0]);
    }

    public function testCreateFieldsMetadataReturnCollectionMetada()
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
            ->method('getAssociationMapping')
            ->will($this->returnValue(['ref-one' => []]));

        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createFieldsMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);
        $this->assertInstanceOf('\Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata', $metadata[0]);
    }

    public function testCreateRelationMetadata()
    {
        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createRelationMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertEmpty($metadata);
    }

    public function testCreateRelationReturnRelationMetada()
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

        $factory = new MetadataFactory($this->configProvider, $this->entityManager);
        $metadata = $factory->createRelationMetadata(self::ENTITY);
        $this->assertInternalType('array', $metadata);
        $this->assertNotEmpty($metadata);
    }
}
