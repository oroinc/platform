<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetRelationConfig;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\RelationConfigMerger;
use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\AdvancedUserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class LoadFromConfigBagTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigBagInterface */
    private $configBag;

    /** @var LoadFromConfigBag */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->configBag = $this->createMock(ConfigBagInterface::class);

        $configBagRegistry = $this->createMock(ConfigBagRegistry::class);
        $configBagRegistry->expects(self::any())
            ->method('getConfigBag')
            ->willReturn($this->configBag);

        $this->processor = new LoadFromConfigBag(
            $this->configExtensionRegistry,
            new ConfigLoaderFactory($this->configExtensionRegistry),
            $configBagRegistry,
            new RelationConfigMerger($this->configExtensionRegistry)
        );
    }

    public function testProcessWhenConfigAlreadyExists()
    {
        $config = [];

        $this->configBag->expects(self::never())
            ->method('getRelationConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
    }

    public function testProcessWhenNoConfigIsReturnedFromConfigBag()
    {
        $this->configBag->expects(self::exactly(2))
            ->method('getRelationConfig')
            ->willReturnMap([
                [UserProfile::class, $this->context->getVersion(), null],
                [User::class, $this->context->getVersion(), null]
            ]);

        $this->context->setClassName(UserProfile::class);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithInheritanceWhenNoParentConfigIsReturnedFromConfigBag()
    {
        $this->configBag->expects(self::exactly(2))
            ->method('getRelationConfig')
            ->willReturnMap([
                [UserProfile::class, $this->context->getVersion(), ['inherit' => true]],
                [User::class, $this->context->getVersion(), null]
            ]);

        $this->context->setClassName(UserProfile::class);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenConfigWithoutInheritanceIsReturnedFromConfigBag()
    {
        $this->configBag->expects(self::once())
            ->method('getRelationConfig')
            ->with(UserProfile::class, $this->context->getVersion())
            ->willReturn(['inherit' => false]);

        $this->context->setClassName(UserProfile::class);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutInheritance()
    {
        $config = [
            'collapse' => true,
            'fields'   => [
                'field1' => null,
                'field2' => null,
                'field3' => null
            ],
            'filters'  => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field1' => null
                ]
            ]
        ];

        $this->configBag->expects(self::once())
            ->method('getRelationConfig')
            ->with(User::class, $this->context->getVersion())
            ->willReturn($config);

        $this->context->setClassName(User::class);
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'collapse' => true,
                'fields'   => [
                    'field1' => null,
                    'field2' => null,
                    'field3' => null
                ]
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $this->context->getFilters()
        );
        self::assertFalse($this->context->hasSorters());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWithInheritance()
    {
        $config = [
            'collapse' => false,
            'order_by' => [
                'field2' => 'ASC'
            ],
            'fields'   => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => null,
                'field4' => null
            ],
            'filters'  => [
                'fields' => [
                    'field1' => null,
                    'field2' => null
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field1' => null,
                    'field2' => null
                ]
            ]
        ];
        $parentConfig2 = [
            'collapse' => true,
            'inherit'  => false,
            'order_by' => [
                'field3' => 'ASC'
            ],
            'fields'   => [
                'field3' => [
                    'exclude' => true
                ]
            ],
            'filters'  => [
                'fields' => [
                    'field3' => null
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field3' => null
                ]
            ]
        ];

        $this->configBag->expects(self::exactly(3))
            ->method('getRelationConfig')
            ->willReturnMap([
                [AdvancedUserProfile::class, $this->context->getVersion(), $config],
                [UserProfile::class, $this->context->getVersion(), null],
                [User::class, $this->context->getVersion(), $parentConfig2]
            ]);

        $this->context->setClassName(AdvancedUserProfile::class);
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'order_by' => [
                    'field2' => 'ASC'
                ],
                'fields'   => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                    'field4' => null
                ]
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                    'field2' => null,
                    'field3' => null
                ]
            ],
            $this->context->getFilters()
        );
        self::assertFalse($this->context->hasSorters());
    }

    public function testProcessWithInheritanceAndNoConfigIsReturnedFromConfigBag()
    {
        $parentConfig1 = [
            'order_by' => [
                'field1' => 'ASC'
            ],
            'fields'   => [
                'field1' => [
                    'property_path' => 'realField1'
                ]
            ],
            'filters'  => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field1' => null
                ]
            ]
        ];

        $this->configBag->expects(self::exactly(2))
            ->method('getRelationConfig')
            ->willReturnMap([
                [UserProfile::class, $this->context->getVersion(), null],
                [User::class, $this->context->getVersion(), $parentConfig1]
            ]);

        $this->context->setClassName(UserProfile::class);
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'order_by' => [
                    'field1' => 'ASC'
                ],
                'fields'   => [
                    'field1' => [
                        'property_path' => 'realField1'
                    ]
                ]
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $this->context->getFilters()
        );
        self::assertFalse($this->context->hasSorters());
    }
}
