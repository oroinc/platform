<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class EntityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var EntityProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $exclusionProvider;

    /**
     * @var Config
     */
    protected $extendConfig;

    protected function setUp()
    {
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfig        = new Config(new EntityConfigId('extend', 'testClass'));
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will(
                $this->returnCallback(
                    function ($entityName) {
                        return str_replace(':', '\\Entity\\', $entityName);
                    }
                )
            );
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->exclusionProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isResourceEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new EntityProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $translator,
            $this->featureChecker
        );
        $this->provider->setExclusionProvider($this->exclusionProvider);
    }

    public function testGetEntity()
    {
        $entityName      = 'Acme:Test';
        $entityClassName = 'Acme\Entity\Test';
        $entityConfig    = $this->getEntityConfig(
            $entityClassName,
            [
                'label'        => 'Test Label',
                'plural_label' => 'Test Plural Label',
                'icon'         => 'fa-test',
            ]
        );
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($entityClassName)
            ->will($this->returnValue($entityConfig));

        $result = $this->provider->getEntity($entityName);

        $expected = [
            'name'         => $entityClassName,
            'label'        => 'Test Label',
            'plural_label' => 'Test Plural Label',
            'icon'         => 'fa-test',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetEnabledEntity()
    {
        $entityName = 'Acme:Test';
        $entityClassName = 'Acme\Entity\Test';
        $entityConfig = $this->getEntityConfig(
            $entityClassName,
            [
                'label'        => 'Test Label',
                'plural_label' => 'Test Plural Label',
                'icon'         => 'fa-test'
            ]
        );
        $entityExtendConfig = $this->getEntityConfig(
            $entityClassName,
            [
                'state' => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClassName)
            ->willReturn($entityConfig);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($entityConfig->getId())
            ->willReturn($entityExtendConfig);
        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($entityClassName, 'entities')
            ->willReturn(true);

        $result = $this->provider->getEnabledEntity($entityName);

        $expected = [
            'name'         => $entityClassName,
            'label'        => 'Test Label',
            'plural_label' => 'Test Plural Label',
            'icon'         => 'fa-test'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     */
    public function testGetEnabledEntityWhenEntityIsNotAccessibleYet()
    {
        $entityName = 'Acme:Test';
        $entityClassName = 'Acme\Entity\Test';
        $entityConfig = $this->getEntityConfig(
            $entityClassName,
            [
                'label'        => 'Test Label',
                'plural_label' => 'Test Plural Label',
                'icon'         => 'fa-test'
            ]
        );
        $entityExtendConfig = $this->getEntityConfig(
            $entityClassName,
            [
                'state' => ExtendScope::STATE_NEW
            ]
        );

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClassName)
            ->willReturn($entityConfig);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($entityConfig->getId())
            ->willReturn($entityExtendConfig);
        $this->featureChecker->expects($this->never())
            ->method('isResourceEnabled');

        $result = $this->provider->getEnabledEntity($entityName);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetEntities()
    {
        $entityClassName1 = 'Acme\Entity\Test1';
        $entityClassName2 = 'Acme\Entity\Test2';
        $entityClassName3 = 'Acme\Entity\Test3';
        $entityClassName4 = 'Acme\Entity\Test4';
        $entityClassName5 = 'Acme\Entity\Test5';

        $entityConfig1 = $this->getEntityConfig(
            $entityClassName1,
            [
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'fa-test1',
            ]
        );
        $entityConfig2 = $this->getEntityConfig(
            $entityClassName2,
            [
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'fa-test2',
            ]
        );
        $entityConfig3 = $this->getEntityConfig(
            $entityClassName3,
            [
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'fa-test3',
            ]
        );
        $entityConfig4 = $this->getEntityConfig(
            $entityClassName4,
            [
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'fa-test3',
            ]
        );
        $entityConfig5 = $this->getEntityConfig(
            $entityClassName5,
            [
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'fa-test3',
            ]
        );

        $map = [
            $entityClassName1 => $entityConfig1,
            $entityClassName2 => $entityConfig2,
            $entityClassName3 => $entityConfig3,
            $entityClassName4 => $entityConfig4,
            $entityClassName5 => $entityConfig5,
        ];

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigById')
            ->will(
                $this->returnCallback(
                    function (EntityConfigId $configId) use ($map) {
                        $className = $configId->getClassName();

                        /** @var ConfigInterface $config */
                        $config = $map[$className];
                        $config->set('state', ExtendScope::STATE_ACTIVE);

                        return $config;
                    }
                )
            );

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$entityClassName1, $entityConfig1],
                        [$entityClassName2, $entityConfig2],
                        [$entityClassName3, $entityConfig3],
                        [$entityClassName4, $entityConfig4],
                        [$entityClassName5, $entityConfig5],
                    ]
                )
            );

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValue(
                    [
                        $entityConfig1,
                        $entityConfig2,
                        $entityConfig3,
                    ]
                )
            );


        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($param) {
                        $this->extendConfig->set('state', ExtendScope::STATE_ACTIVE);
                        if ($param == 'Acme\Entity\Test4') {
                            $this->extendConfig->set('state', ExtendScope::STATE_NEW);
                        }
                        if ($param == 'Acme\Entity\Test4') {
                            $this->extendConfig->set('state', ExtendScope::STATE_DELETE);
                        }
                        return $this->extendConfig;
                    }
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->extendConfig));

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(true);

        // sort by plural label
        $result   = $this->provider->getEntities();
        $expected = [
            [
                'name'         => $entityClassName2,
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'fa-test2',
            ],
            [
                'name'         => $entityClassName1,
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'fa-test1',
            ],
            [
                'name'         => $entityClassName3,
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'fa-test3',
            ],
        ];

        $this->assertEquals($expected, $result);

        // sort by label
        $result   = $this->provider->getEntities(false);
        $expected = [
            [
                'name'         => $entityClassName3,
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'fa-test3',
            ],
            [
                'name'         => $entityClassName2,
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'fa-test2',
            ],
            [
                'name'         => $entityClassName1,
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'fa-test1',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetEntitiesWhenEntitiesAreDisabled()
    {
        $entityClassName1 = 'Acme\Entity\Test1';
        $entityClassName2 = 'Acme\Entity\Test2';
        $entityClassName3 = 'Acme\Entity\Test3';

        $entityConfig1 = $this->getEntityConfig(
            $entityClassName1,
            [
                'label' => 'C',
                'plural_label' => 'B',
                'icon' => 'fa-test1',
            ]
        );
        $entityConfig2 = $this->getEntityConfig(
            $entityClassName2,
            [
                'label' => 'B',
                'plural_label' => 'A',
                'icon' => 'fa-test2',
            ]
        );
        $entityConfig3 = $this->getEntityConfig(
            $entityClassName3,
            [
                'label' => 'A',
                'plural_label' => 'C',
                'icon' => 'fa-test3',
            ]
        );

        $map = [
            $entityClassName1 => $entityConfig1,
            $entityClassName2 => $entityConfig2,
            $entityClassName3 => $entityConfig3,
        ];

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigById')
            ->will(
                $this->returnCallback(
                    function (EntityConfigId $configId) use ($map) {
                        $className = $configId->getClassName();

                        /** @var ConfigInterface $config */
                        $config = $map[$className];
                        $config->set('state', ExtendScope::STATE_ACTIVE);

                        return $config;
                    }
                )
            );

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$entityClassName1, $entityConfig1],
                        [$entityClassName2, $entityConfig2],
                        [$entityClassName3, $entityConfig3],
                    ]
                )
            );

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValue(
                    [
                        $entityConfig1,
                        $entityConfig2,
                        $entityConfig3,
                    ]
                )
            );

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->extendConfig));


        $this->featureChecker->expects($this->at(0))
            ->method('isResourceEnabled')
            ->with($entityClassName1)
            ->willReturn(true);
        $this->featureChecker->expects($this->at(1))
            ->method('isResourceEnabled')
            ->with($entityClassName2)
            ->willReturn(false);
        $this->featureChecker->expects($this->at(2))
            ->method('isResourceEnabled')
            ->with($entityClassName3)
            ->willReturn(true);

        // sort by plural label
        $result = $this->provider->getEntities();
        $expected = [
            [
                'name' => $entityClassName1,
                'label' => 'C',
                'plural_label' => 'B',
                'icon' => 'fa-test1',
            ],
            [
                'name' => $entityClassName3,
                'label' => 'A',
                'plural_label' => 'C',
                'icon' => 'fa-test3',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @param string $entityClassName
     * @param array  $values
     * @param string $scope
     *
     * @return Config
     */
    protected function getEntityConfig($entityClassName, $values, $scope = 'entity')
    {
        $entityConfigId = new EntityConfigId($scope, $entityClassName);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }

    public function testIsIgnoredEntity()
    {
        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredEntity')
            ->willReturn(true);

        $this->assertTrue($this->provider->isIgnoredEntity(\stdClass::class));
    }
}
