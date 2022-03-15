<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ConfigTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationHelper;

    /** @var ConfigSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translationHelper = $this->createMock(ConfigTranslationHelper::class);

        $this->subscriber = new ConfigSubscriber(
            $this->translationHelper,
            $this->configManager,
            $this->translator
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
    public function testPreSetData(array $data, ConfigModel $model, array $trans, array $expectedData = null)
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
            ->willReturn(new Config(new EntityConfigId('extend')));
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
            ->willReturnCallback(function ($configModel, $scope) {
                return new EntityConfigId($scope, 'Entity\Test');
            });
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($provider1);
        $this->translationHelper->expects($this->any())
            ->method('translateWithFallback')
            ->willReturnCallback(function ($id, $fallback) use (&$trans) {
                return $trans[$id] ?? $fallback;
            });

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn($providers);

        $event = $this->getFormEvent($data, $model);
        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData ?: $data);

        $this->subscriber->preSetData($event);
    }

    /**
     * @dataProvider postSubmitProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPostSubmit(
        array $data,
        bool $isValid,
        ConfigModel $model,
        array $trans,
        ?array $expectedConfigData,
        array $expectedTrans
    ) {
        $extendProvider = $this->getConfigProvider('extend', [], false);
        $config = new Config(new EntityConfigId('extend'));
        $extendProvider->expects($this->once())
            ->method('getConfigById')
            ->willReturn($config);

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
                ->willReturn($config1);
        } else {
            $provider1->expects($this->never())
                ->method('getConfigById');
        }
        $providers = new ArrayCollection();
        $providers->add($provider1);

        $this->configManager->expects($this->any())
            ->method('getConfigIdByModel')
            ->willReturnCallback(function ($configModel, $scope) {
                return new EntityConfigId($scope, 'Entity\Test');
            });
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendProvider);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) use (&$trans) {
                return $trans[$id] ?? $id;
            });

        $form = $this->createMock(FormInterface::class);

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn($providers);

        if (null === $expectedConfigData) {
            $this->configManager->expects($this->never())
                ->method('persist');
        } else {
            $expectedConfig = new Config(new EntityConfigId('entity', 'Entity\Test'));
            foreach ($expectedConfigData as $code => $val) {
                $expectedConfig->set($code, $val);
            }
            $this->configManager->expects($this->once())
                ->method('persist')
                ->with($expectedConfig);
        }

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn($isValid);

        if ($isValid) {
            $this->translationHelper->expects($this->once())
                ->method('saveTranslations')
                ->with($expectedTrans);
            $this->configManager->expects($this->once())
                ->method('flush');
        } else {
            $this->translationHelper->expects($this->never())
                ->method('saveTranslations');
            $this->configManager->expects($this->never())
                ->method('flush');
        }

        $this->configManager->expects($this->any())
            ->method('calculateConfigChangeSet')
            ->with($config1);
        $this->configManager->expects($this->any())
            ->method('getConfigChangeSet')
            ->with($config1)
            ->willReturn(['state' => ['Active', 'Requires update']]);

        $event = $this->getFormEvent($data, $model, $form);

        $this->subscriber->postSubmit($event);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function preSetDataProvider(): array
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
    public function postSubmitProvider(): array
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
                []
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
                []
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
                    'label_key' => 'translated label',
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
                    'label_key' => 'translated label',
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
                    'label_key' => 'translated label',
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
                []
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
                    'label_key' => 'translated label',
                ]
            ],
            'existing model updated field' => [
                [
                    'entity' => [
                        'label' => 'translated label',
                        'icon'  => 'testIcon',
                        'state' => 'Active',
                    ]
                ],
                true,
                $existingConfigModel,
                [
                    'label_key' => 'translated label'
                ],
                [
                    'label' => 'label_key',
                    'icon'  => 'testIcon',
                    'state' => 'Active',
                ],
                []
            ],
        ];
    }

    private function getFormEvent(
        array $data,
        ConfigModel $model,
        FormInterface|\PHPUnit\Framework\MockObject\MockObject|null $form = null
    ): FormEvent|\PHPUnit\Framework\MockObject\MockObject {
        $fieldName = '';
        if ($model instanceof FieldConfigModel && !$model->getId()) {
            $fieldName = $model->getFieldName();
        }

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOption')
            ->withConsecutive(['config_model'], ['field_name'])
            ->willReturnOnConsecutiveCalls($model, $fieldName);

        if (null === $form) {
            $form = $this->createMock(FormInterface::class);
        }
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        return $event;
    }

    private function getConfigProvider(
        string $scope,
        array $configs,
        bool $isGetPropertyConfigExpected
    ): ConfigProvider|\PHPUnit\Framework\MockObject\MockObject {
        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);
        if ($isGetPropertyConfigExpected) {
            $propertyConfig = new PropertyConfigContainer($configs);
            $provider->expects($this->once())
                ->method('getPropertyConfig')
                ->willReturn($propertyConfig);
        } else {
            $provider->expects($this->never())
                ->method('getPropertyConfig');
        }

        return $provider;
    }
}
