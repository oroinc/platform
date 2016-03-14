<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\ExcludeNotAccessibleRelations;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

class ExcludeNotAccessibleRelationsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var ExcludeNotAccessibleRelations */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper  = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router          = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $routerContext = new RequestContext('/root', 'POST');
        $this->router->expects($this->any())
            ->method('getContext')
            ->willReturn($routerContext);

        $this->processor = new ExcludeNotAccessibleRelations(
            $this->doctrineHelper,
            $this->router,
            $this->valueNormalizer
        );
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenNoFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'exclude' => true
                ],
                'association1' => null,
                'association2' => [
                    'exclude' => true
                ],
                'association3' => [
                    'property_path' => 'realAssociation3'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->exactly(3))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['field1', false],
                    ['association1', true],
                    ['realAssociation3', true],
                ]
            );
        $rootEntityMetadata->expects($this->exactly(2))
            ->method('getAssociationMapping')
            ->willReturnMap(
                [
                    ['association1', ['targetEntity' => 'Test\Association1Target']],
                    ['realAssociation3', ['targetEntity' => 'Test\Association3Target']],
                ]
            );

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association3Metadata = $this->getClassMetadataMock('Test\Association3Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                    ['Test\Association3Target', true, $association3Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        'associations1'
                    ],
                    [
                        'Test\Association3Target',
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        'associations3'
                    ],
                ]
            );

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap(
                [
                    [
                        'oro_rest_api_cget',
                        ['entity' => 'associations1'],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                        '/root/api/associations1'
                    ],
                    [
                        'oro_rest_api_cget',
                        ['entity' => 'associations3'],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                        '/root/api/associations3'
                    ],
                ]
            );
        $this->router->expects($this->exactly(2))
            ->method('match')
            ->willReturnMap(
                [
                    ['/api/associations1', ['_route' => 'oro_rest_api_cget_associations1']],
                    ['/api/associations3', ['_route' => 'oro_rest_api_cget_associations3']],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'       => null,
                    'field2'       => [
                        'exclude' => true
                    ],
                    'association1' => null,
                    'association2' => [
                        'exclude' => true
                    ],
                    'association3' => [
                        'property_path' => 'realAssociation3'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityDoesNotHaveAlias()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willThrowException(new EntityAliasNotFoundException());

        $this->router->expects($this->never())
            ->method('generate');
        $this->router->expects($this->never())
            ->method('match');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityDoesNotHaveApiResource()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willReturn('associations1');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_rest_api_cget', ['entity' => 'associations1'])
            ->willThrowException(new RouteNotFoundException());
        $this->router->expects($this->never())
            ->method('match');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenRouterMatchThrowsException()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willReturn('associations1');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_rest_api_cget', ['entity' => 'associations1'])
            ->willReturn('/root/api/associations1');
        $this->router->expects($this->once())
            ->method('match')
            ->with('/api/associations1')
            ->willThrowException(new MethodNotAllowedException(['PUT']));

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenRouterMatchReturnsNotAcceptableResult()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willReturn('associations1');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_rest_api_cget', ['entity' => 'associations1'])
            ->willReturn('/root/api/associations1');
        $this->router->expects($this->once())
            ->method('match')
            ->with('/api/associations1')
            ->willReturn(['_route' => '_webservice_definition']);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityUsesTableInheritance()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata                  = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses      = ['Test\Association1Target1'];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->at(0))
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willThrowException(new EntityAliasNotFoundException());
        $this->valueNormalizer->expects($this->at(1))
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target1',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willReturn('associations1_1');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_rest_api_cget', ['entity' => 'associations1_1'])
            ->willReturn('/root/api/associations1_1');
        $this->router->expects($this->once())
            ->method('match')
            ->with('/api/associations1_1')
            ->willReturn(['_route' => 'oro_rest_api_cget_associations1_1']);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityUsesTableInheritanceAndNoApiResourceForAnyConcreteTargetEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata                  = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses      = ['Test\Association1Target1'];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->at(0))
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willThrowException(new EntityAliasNotFoundException());
        $this->valueNormalizer->expects($this->at(1))
            ->method('normalizeValue')
            ->with(
                'Test\Association1Target1',
                DataType::ENTITY_TYPE,
                $this->context->getRequestType(),
                false
            )
            ->willReturn('associations1_1');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_rest_api_cget', ['entity' => 'associations1_1'])
            ->willReturn('/root/api/associations1_1');
        $this->router->expects($this->once())
            ->method('match')
            ->with('/api/associations1_1')
            ->willThrowException(new RouteNotFoundException());

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
