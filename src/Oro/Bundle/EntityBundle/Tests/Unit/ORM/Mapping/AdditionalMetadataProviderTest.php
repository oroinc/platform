<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Mapping;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;

class AdditionalMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AdditionalMetadataProvider */
    protected $additionalMetadataProvider;

    /** @var ClassMetadataFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    public function setUp()
    {
        $this->metadataFactory = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory');

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->additionalMetadataProvider = new AdditionalMetadataProvider(
            $registry,
            new ArrayCache()
        );
    }

    public function testGetInversedUnidirectionalAssociationMappings()
    {
        $entityMetadata = new ClassMetadata('Namespace\EntityName');

        $fooMetadata = new ClassMetadata('Namespace\\FooEntity');
        $fooMetadata->associationMappings = [
            'foo_association' => [
                'fieldName' => 'foo_association',
                'type' => ClassMetadata::ONE_TO_MANY,
                'targetEntity' => 'Namespace\EntityName',
            ],
        ];

        $barMetadata = new ClassMetadata('Namespace\\BarEntity');
        $barMetadata->associationMappings = [
            'bar_association' => [
                'fieldName' => 'bar_association',
                'type' => ClassMetadata::ONE_TO_MANY,
                'targetEntity' => 'Namespace\EntityName',
            ],
            'skipped_many_to_many' => [
                'fieldName' => 'skipped_many_to_many',
                'type' => ClassMetadata::MANY_TO_MANY,
                'targetEntity' => 'Namespace\EntityName'
            ],
            'skipped_mapped_by' => [
                'fieldName' => 'skipped_mapped_by',
                'mappedBy' => 'Namespace\EntityName',
                'targetEntity' => 'Namespace\EntityName',
            ],
        ];

        $fooBarMetadata = new ClassMetadata('Namespace\\FooBarEntity');
        $fooBarMetadata->associationMappings = [
            'bar_association' => [
                'fieldName' => 'bar_association',
                'type' => ClassMetadata::ONE_TO_ONE,
                'targetEntity' => 'Namespace\EntityName',
            ],
        ];

        $allMetadata = [
            $entityMetadata,
            $fooMetadata,
            $barMetadata,
            $fooBarMetadata,
        ];

        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($allMetadata));

        $expectedMetadata = [
            [
                'fieldName' => 'foo_association',
                'type' => ClassMetadata::ONE_TO_MANY,
                'targetEntity' => 'Namespace\\EntityName',
                'mappedBySourceEntity' => false,
                '_generatedFieldName' => 'Namespace_FooEntity_foo_association',
            ],
            [
                'fieldName' => 'bar_association',
                'type' => ClassMetadata::ONE_TO_MANY,
                'targetEntity' => 'Namespace\\EntityName',
                'mappedBySourceEntity' => false,
                '_generatedFieldName' => 'Namespace_BarEntity_bar_association',
            ],
            [
                'fieldName' => 'bar_association',
                'type' => ClassMetadata::ONE_TO_ONE,
                'targetEntity' => 'Namespace\\EntityName',
                'mappedBySourceEntity' => false,
                '_generatedFieldName' => 'Namespace_FooBarEntity_bar_association',
            ],
        ];

        $this->assertEquals(
            $expectedMetadata,
            $this->additionalMetadataProvider->getInversedUnidirectionalAssociationMappings('Namespace\EntityName')
        );
    }
}
