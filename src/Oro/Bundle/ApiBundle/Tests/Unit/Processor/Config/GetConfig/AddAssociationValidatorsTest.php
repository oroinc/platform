<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AddAssociationValidators;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;

class AddAssociationValidatorsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var AddAssociationValidators */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new AddAssociationValidators($this->doctrineHelper);
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
                'association2' => [
                    'target_class' => 'Test\Association2Target',
                    'target_type'  => 'to-many',
                    'form_options' => ['test_option' => 'test_value']
                ],
                'association3' => [
                    'target_class' => 'Test\Association3Target',
                    'target_type'  => 'to-many',
                    'property_path' => 'realAssociation3'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNull($configObject->getField('field1')->getFormOptions());
        $this->assertNull($configObject->getField('association1')->getFormOptions());
        $this->assertEquals(
            [
                'test_option' => 'test_value',
                'constraints' => [
                    new HasAdderAndRemover(['class' => self::TEST_CLASS_NAME, 'property' => 'association2'])
                ]
            ],
            $configObject->getField('association2')->getFormOptions()
        );
        $this->assertEquals(
            [
                'constraints' => [
                    new HasAdderAndRemover(['class' => self::TEST_CLASS_NAME, 'property' => 'realAssociation3'])
                ]
            ],
            $configObject->getField('association3')->getFormOptions()
        );
    }

    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'association1' => null,
                'association2' => [
                    'form_options' => ['test_option' => 'test_value']
                ],
                'association3' => [
                    'property_path' => 'realAssociation3'
                ],
            ]
        ];

        $entityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $entityMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['field1', false],
                    ['association1', true],
                    ['association2', true],
                    ['realAssociation3', true],
                ]
            );
        $entityMetadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap(
                [
                    ['association2', true],
                    ['realAssociation3', true],
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($entityMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNull($configObject->getField('field1')->getFormOptions());
        $this->assertEquals(
            [
                'constraints' => [new AccessGranted()]
            ],
            $configObject->getField('association1')->getFormOptions()
        );
        $this->assertEquals(
            [
                'test_option' => 'test_value',
                'constraints' => [
                    new HasAdderAndRemover(['class' => self::TEST_CLASS_NAME, 'property' => 'association2']),
                    new All(new AccessGranted())
                ]
            ],
            $configObject->getField('association2')->getFormOptions()
        );
        $this->assertEquals(
            [
                'constraints' => [
                    new HasAdderAndRemover(['class' => self::TEST_CLASS_NAME, 'property' => 'realAssociation3']),
                    new All(new AccessGranted())
                ]
            ],
            $configObject->getField('association3')->getFormOptions()
        );
    }
}
