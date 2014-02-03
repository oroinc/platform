<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

class EntityProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityClassResolver;

    /** @var EntityProvider */
    private $provider;

    /**
     * @var Config
     */
    protected $config;

    protected function setUp()
    {
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config               = new Config(new EntityConfigId('testClass', 'test'));
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
        $this->provider = new EntityProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $translator
        );
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
                'icon'         => 'icon-test',
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
            'icon'         => 'icon-test',
        ];

        $this->assertEquals($expected, $result);
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
                'icon'         => 'icon-test1',
            ]
        );
        $entityConfig2 = $this->getEntityConfig(
            $entityClassName2,
            [
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'icon-test2',
            ]
        );
        $entityConfig3 = $this->getEntityConfig(
            $entityClassName3,
            [
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'icon-test3',
            ]
        );
        $entityConfig4 = $this->getEntityConfig(
            $entityClassName4,
            [
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'icon-test3',
            ]
        );
        $entityConfig5 = $this->getEntityConfig(
            $entityClassName5,
            [
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'icon-test3',
            ]
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
                        $this->config->set('state', ExtendManager::STATE_ACTIVE);
                        if ($param == 'Acme\Entity\Test4') {
                            $this->config->set('state', ExtendManager::STATE_NEW);
                        }
                        if ($param == 'Acme\Entity\Test4') {
                            $this->config->set('state', ExtendManager::STATE_DELETED);
                        }
                        return $this->config;
                    }
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        // sort by plural label
        $result   = $this->provider->getEntities();
        $expected = [
            [
                'name'         => $entityClassName2,
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'icon-test2',
            ],
            [
                'name'         => $entityClassName1,
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'icon-test1',
            ],
            [
                'name'         => $entityClassName3,
                'label'        => 'A',
                'plural_label' => 'C',
                'icon'         => 'icon-test3',
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
                'icon'         => 'icon-test3',
            ],
            [
                'name'         => $entityClassName2,
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'icon-test2',
            ],
            [
                'name'         => $entityClassName1,
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'icon-test1',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    protected function getEntityConfig($entityClassName, $values)
    {
        $entityConfigId = new EntityConfigId($entityClassName, 'entity');
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }
}
