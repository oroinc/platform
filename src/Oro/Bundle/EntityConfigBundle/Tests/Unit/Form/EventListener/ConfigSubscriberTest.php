<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dbTranslationMetadataCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var ConfigSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        $this->configManager              =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->translator                 =
            $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
                ->disableOriginalConstructor()
                ->getMock();
        $this->dbTranslationMetadataCache =
            $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
                ->disableOriginalConstructor()
                ->getMock();
        $this->doctrine                   = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new ConfigSubscriber(
            $this->doctrine,
            $this->configManager,
            $this->translator,
            $this->dbTranslationMetadataCache
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::POST_SUBMIT  => ['postSubmit', -10],
                FormEvents::PRE_SET_DATA => 'preSetData'
            ],
            ConfigSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData($data, $model, $trans, $expectedData)
    {
        $provider1 = $this->getConfigProvider(
            'entity',
            [
                'entity' => [
                    'items' => [
                        'icon'  => [],
                        'label' => ['options' => ['translatable' => true]]
                    ]
                ],
                'field'  => [
                    'items' => [
                        'attr'  => [],
                        'label' => ['options' => ['translatable' => true]]
                    ]
                ]
            ],
            isset($data['entity'])
        );
        $provider1->expects($this->once())
            ->method('getConfigById')
            ->will($this->returnValue(new Config(new EntityConfigId('extend'))));
        $provider2 = $this->getConfigProvider(
            'test',
            [
                'entity' => [
                    'items' => [
                        'attr1' => []
                    ]
                ]
            ],
            isset($data['test'])
        );
        $providers = new ArrayCollection();
        $providers->add($provider1);
        $providers->add($provider2);

        $this->configManager->expects($this->any())
            ->method('getConfigIdByModel')
            ->will(
                $this->returnCallback(
                    function ($configModel, $scope) {
                        return new EntityConfigId($scope, 'Entity\Test');
                    }
                )
            );
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($provider1));
        $this->translator->expects($this->any())
            ->method('hasTrans')
            ->will(
                $this->returnCallback(
                    function ($id) use (&$trans) {
                        return isset($trans[$id]);
                    }
                )
            );
        $this->translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id) use (&$trans) {
                        return $trans[$id];
                    }
                )
            );

        $event = $this->getFormEvent($data, $model);
        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue($providers));

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData ?: $data);

        $this->subscriber->preSetData($event);
    }

    /**
     * @dataProvider postSubmitProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPostSubmit($data, $isValid, $model, $trans, $expectedConfigData, $expectedTrans)
    {
        $extendProvider = $this->getConfigProvider('extend', [], false);
        $extendProvider->expects($this->once())
            ->method('getConfigById')
            ->will($this->returnValue(new Config(new EntityConfigId('extend'))));

        $provider1 = $this->getConfigProvider(
            'entity',
            [
                'entity' => [
                    'items' => [
                        'icon'  => [],
                        'label' => ['options' => ['translatable' => true]]
                    ]
                ]
            ],
            isset($data['entity'])
        );
        $config1   = new Config(new EntityConfigId('entity', 'Entity\Test'));
        $config1->set('label', 'label_key');
        if (isset($data['entity'])) {
            $provider1->expects($this->once())
                ->method('getConfigById')
                ->with($config1->getId())
                ->will($this->returnValue($config1));
        } else {
            $provider1->expects($this->never())
                ->method('getConfigById');
        }
        $providers = new ArrayCollection();
        $providers->add($provider1);

        $this->configManager->expects($this->any())
            ->method('getConfigIdByModel')
            ->will(
                $this->returnCallback(
                    function ($configModel, $scope) {
                        return new EntityConfigId($scope, 'Entity\Test');
                    }
                )
            );
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendProvider));
        $this->translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id) use (&$trans) {
                        if (isset($trans[$id])) {
                            return $trans[$id];
                        } else {
                            return $id;
                        }
                    }
                )
            );

        $form  = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $event = $this->getFormEvent($data, $model, $form);
        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue($providers));

        if (null === $expectedConfigData) {
            $this->configManager->expects($this->never())
                ->method('persist');
        } else {
            $expectedConfig = new Config(new EntityConfigId('entity', 'Entity\Test'));
            foreach ($expectedConfigData as $code => $val) {
                $expectedConfig->set($code, $val);
            }
            $this->configManager->expects($this->exactly(1))
                ->method('persist')
                ->withConsecutive(
                    [$expectedConfig]
                );
        }

        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue($isValid));
        if (null === $expectedTrans) {
            $this->translator->expects($this->never())
                ->method('getLocale');
            $this->dbTranslationMetadataCache->expects($this->never())
                ->method('updateTimestamp');
        } else {
            $translationEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $translationValue = new Translation();

            $this->translator->expects($this->once())
                ->method('getLocale')
                ->will($this->returnValue('testLocale'));
            $this->dbTranslationMetadataCache->expects($this->once())
                ->method('updateTimestamp')
                ->with('testLocale');
            $repo = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $this->doctrine->expects($this->once())
                ->method('getManagerForClass')
                ->with(Translation::ENTITY_NAME)
                ->will($this->returnValue($translationEm));
            $translationEm->expects($this->once())
                ->method('getRepository')
                ->with(Translation::ENTITY_NAME)
                ->will($this->returnValue($repo));
            $repo->expects($this->once())
                ->method('saveValue')
                ->with(
                    $expectedTrans[0],
                    $expectedTrans[1],
                    $expectedTrans[2],
                    TranslationRepository::DEFAULT_DOMAIN,
                    Translation::SCOPE_UI
                )
                ->willReturn($translationValue);

            $translationEm->expects($this->once())
                ->method('flush')
                ->with([$translationValue]);
        }
        if ($isValid) {
            $this->configManager->expects($this->once())
                ->method('flush');
        } else {
            $this->configManager->expects($this->never())
                ->method('flush');
        }

        $this->subscriber->postSubmit($event);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function preSetDataProvider()
    {
        $existingFieldConfigModel = new FieldConfigModel('testField', 'string');
        ReflectionUtil::setId($existingFieldConfigModel, 1);

        return [
            'empty data (entity)'                                => [
                [],
                new EntityConfigModel('Entity\Test'),
                [],
                null
            ],
            'empty data (field)'                                 => [
                [],
                new FieldConfigModel('testField', 'string'),
                [],
                null
            ],
            'new model without trans (entity)'                   => [
                [
                    'entity' => [
                        'label' => 'testLabel',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                new EntityConfigModel('Entity\Test'),
                [],
                [
                    'entity' => [
                        'label' => '',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
            ],
            'new model without trans (field)'                    => [
                [
                    'entity' => [
                        'label' => 'testLabel'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                new FieldConfigModel('testField', 'string'),
                [],
                [
                    'entity' => [
                        'label' => 'testField',
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
            ],
            'existing model without trans (field)'               => [
                [
                    'entity' => [
                        'label' => 'testLabel'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                $existingFieldConfigModel,
                [],
                [
                    'entity' => [
                        'label' => '',
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
            ],
            'new model without translatable attributes (entity)' => [
                [
                    'entity' => [
                        'icon' => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                new EntityConfigModel('Entity\Test'),
                [],
                null
            ],
            'new model with trans (entity)'                      => [
                [
                    'entity' => [
                        'label' => 'testLabel',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                new EntityConfigModel('Entity\Test'),
                [
                    'testLabel' => 'translated label'
                ],
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ]
            ],
            'new model with trans (field)'                       => [
                [
                    'entity' => [
                        'label' => 'testLabel',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                new FieldConfigModel('testField', 'string'),
                [
                    'testLabel' => 'translated label'
                ],
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ]
            ],
            'existing model with trans (field)'                  => [
                [
                    'entity' => [
                        'label' => 'testLabel',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ],
                $existingFieldConfigModel,
                [
                    'testLabel' => 'translated label'
                ],
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ],
                    'test'   => [
                        'attr1' => 'testAttr'
                    ]
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function postSubmitProvider()
    {
        $existingConfigModel = new EntityConfigModel('Entity\Test');
        ReflectionUtil::setId($existingConfigModel, 1);

        return [
            'empty data'                                         => [
                [],
                false,
                new EntityConfigModel('Entity\Test'),
                [],
                null,
                null
            ],
            'new model without trans (isValid=false)'            => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ]
                ],
                false,
                new EntityConfigModel('Entity\Test'),
                [],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon'
                ],
                null
            ],
            'new model without trans (isValid=true)'             => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ]
                ],
                true,
                new EntityConfigModel('Entity\Test'),
                [],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon'
                ],
                [
                    'label_key',
                    'translated label',
                    'testLocale'
                ]
            ],
            'existing model without trans (isValid=true)'        => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ]
                ],
                true,
                $existingConfigModel,
                [],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon'
                ],
                [
                    'label_key',
                    'translated label',
                    'testLocale'
                ]
            ],
            'new model with trans (isValid=true)'                => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ]
                ],
                true,
                new EntityConfigModel('Entity\Test'),
                [
                    'label_key' => 'translated label'
                ],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon'
                ],
                [
                    'label_key',
                    'translated label',
                    'testLocale'
                ]
            ],
            'existing model with trans (isValid=true)'           => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ]
                ],
                true,
                $existingConfigModel,
                [
                    'label_key' => 'translated label'
                ],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon'
                ],
                null
            ],
            'existing model with different trans (isValid=true)' => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon'
                    ]
                ],
                true,
                $existingConfigModel,
                [
                    'label_key' => 'translated label 1'
                ],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon',
                ],
                [
                    'label_key',
                    'translated label',
                    'testLocale'
                ]
            ],
        ];
    }

    /**
     * @param array                                    $data
     * @param ConfigModel                              $model
     * @param \PHPUnit_Framework_MockObject_MockObject $form
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormEvent($data, $model, $form = null)
    {
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('config_model')
            ->will($this->returnValue($model));

        if (null === $form) {
            $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        }
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        return $event;
    }

    /**
     * @param string $scope
     * @param array  $configs
     * @param bool   $isGetPropertyConfigExpected
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProvider($scope, $configs, $isGetPropertyConfigExpected)
    {
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->any())
            ->method('getScope')
            ->will($this->returnValue($scope));
        if ($isGetPropertyConfigExpected) {
            $propertyConfig = new PropertyConfigContainer($configs);
            $provider->expects($this->once())
                ->method('getPropertyConfig')
                ->will($this->returnValue($propertyConfig));
        } else {
            $provider->expects($this->never())
                ->method('getPropertyConfig');
        }

        return $provider;
    }
}
