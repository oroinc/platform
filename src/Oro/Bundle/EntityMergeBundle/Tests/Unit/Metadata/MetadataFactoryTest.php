<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class MetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Namespace\EntityName';

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
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(array('getSingleIdentifierFieldName'))
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->setMethods(array('getMetadataFor'))
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(self::ENTITY)
            ->will($this->returnValue($metadata));

        $this->entityManager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->factory = new MetadataFactory($this->configProvider, $this->entityManager);
    }

    public function testGetMergeMetadata()
    {
        $this->factory->getMergeMetadata(self::ENTITY);
    }
}
