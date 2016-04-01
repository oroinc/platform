<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\ExcludeCollectionValuedRelations;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class ExcludeCollectionValuedRelationsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ExcludeCollectionValuedRelations */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ExcludeCollectionValuedRelations($this->doctrineHelper);
    }

    public function testProcessForNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->processor->process($this->context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'toOne1'    => null,
                'toMany1'   => null,
                'excluded1' => [
                    'exclude' => true
                ],
                'toOne2'    => [
                    'property_path' => 'realToOne2'
                ],
                'toMany2'   => [
                    'property_path' => 'realToMany2'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->exactly(4))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['toOne1', true],
                    ['toMany1', true],
                    ['realToOne2', true],
                    ['realToMany2', true],
                ]
            );
        // @todo: temporary exclude all associations. see BAP-10008
        /*$rootEntityMetadata->expects($this->exactly(4))
            ->method('isCollectionValuedAssociation')
            ->willReturnMap(
                [
                    ['toOne1', false],
                    ['toMany1', true],
                    ['realToOne2', false],
                    ['realToMany2', true],
                ]
            );*/

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    // @todo: temporary exclude all associations. see BAP-10008
                    //'toOne1'    => null,
                    'toOne1'    => ['exclude' => true],
                    'toMany1'   => [
                        'exclude' => true
                    ],
                    'excluded1' => [
                        'exclude' => true
                    ],
                    'toOne2'    => [
                        // @todo: temporary exclude all associations. see BAP-10008
                        'exclude'       => true,
                        'property_path' => 'realToOne2'
                    ],
                    'toMany2'   => [
                        'property_path' => 'realToMany2',
                        'exclude'       => true
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }
}
