<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OroEntitySelectOrCreateInlineTypeTest extends FormIntegrationTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRegistry;

    /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var OroEntitySelectOrCreateInlineType|\PHPUnit\Framework\MockObject\MockObject */
    private $formType;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->config = $this->createMock(ConfigInterface::class);

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturn($configProvider);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects($this->any())
            ->method('getProperties')
            ->willReturn([]);

        $this->searchRegistry = $this->createMock(SearchRegistry::class);
        $this->searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->willReturn($searchHandler);

        $this->formType = new OroEntitySelectOrCreateInlineType(
            $this->authorizationChecker,
            $this->featureChecker,
            $configManager,
            $this->entityManager,
            $this->searchRegistry
        );

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->any())
            ->method('get')
            ->willReturn('value');

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        return [
            new PreloadedExtension([$this->formType], []),
            new EntitySelectOrCreateInlineFormExtension(
                $this->entityManager,
                $this->searchRegistry,
                $configProvider
            )
        ];
    }

    /**
     * @dataProvider formTypeDataProvider
     */
    public function testExecute(
        array $inputOptions,
        array $expectedOptions,
        ?bool $createRouteEnabled,
        ?bool $aclGranted,
        array $expectedViewVars = []
    ) {
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($argument) use ($inputOptions) {
                return $inputOptions[$argument] ?? null;
            });

        if (null === $createRouteEnabled) {
            $this->featureChecker->expects(self::never())
                ->method('isResourceEnabled');
        } else {
            $this->featureChecker->expects(self::atLeastOnce())
                ->method('isResourceEnabled')
                ->with($inputOptions['create_form_route'], 'routes')
                ->willReturn($createRouteEnabled);
        }

        if (null === $aclGranted) {
            $this->authorizationChecker->expects(self::never())
                ->method('isGranted');
        } elseif (!empty($inputOptions['create_acl'])) {
            $this->authorizationChecker->expects(self::atLeastOnce())
                ->method('isGranted')
                ->with($inputOptions['create_acl'])
                ->willReturn($aclGranted);
        } else {
            $this->authorizationChecker->expects(self::atLeastOnce())
                ->method('isGranted')
                ->with('CREATE', 'entity:' . TestEntity::class)
                ->willReturn($aclGranted);
        }

        $form = $this->factory->create(OroEntitySelectOrCreateInlineType::class, null, $inputOptions);
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertTrue($form->getConfig()->hasOption($name), sprintf('Expected option %s not found', $name));
            $this->assertEquals(
                $expectedValue,
                $form->getConfig()->getOption($name),
                sprintf('Option %s value is incorrect', $name)
            );
        }

        $form->submit(null);

        $formView = $form->createView();
        foreach ($expectedViewVars as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $formView->vars, sprintf('View vars %s not found', $name));
            $this->assertEquals($expectedValue, $formView->vars[$name], sprintf('View var %s is incorrect', $name));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formTypeDataProvider(): array
    {
        $converter = $this->createMock(ConverterInterface::class);

        return [
            'create not granted'                => [
                [
                    'grid_widget_route' => 'some_route',
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => TestEntity::class,
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => false
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ],
                null,
                false,
                [
                    'grid_widget_route'       => 'some_route',
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ]
            ],
            'create no route'                   => [
                [
                    'grid_name'      => 'test',
                    'converter'      => $converter,
                    'entity_class'   => TestEntity::class,
                    'configs'        => [
                        'route_name' => 'test'
                    ],
                    'create_enabled' => true
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ],
                null,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ]
            ],
            'create has route not granted'      => [
                [
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => TestEntity::class,
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => false,
                    'create_form_route' => 'test',
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ],
                true,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ]
            ],
            'create enabled acl not granted'    => [
                [
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => TestEntity::class,
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => true,
                    'create_form_route' => 'test',
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ],
                true,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ]
            ],
            'create enabled acl granted'        => [
                [
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => TestEntity::class,
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => true,
                    'create_form_route' => 'test',
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => true
                ],
                true,
                true,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => true
                ]
            ],
            'create enabled acl granted custom' => [
                [
                    'grid_name'                    => 'test',
                    'grid_parameters'              => ['testParam1' => 1],
                    'grid_render_parameters'       => ['testParam2' => 2],
                    'converter'                    => $converter,
                    'entity_class'                 => TestEntity::class,
                    'configs'                      => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'               => true,
                    'create_form_route'            => 'test',
                    'create_form_route_parameters' => ['name' => 'US'],
                    'create_acl'                   => 'acl',
                ],
                [
                    'grid_name'                    => 'test',
                    'grid_parameters'              => ['testParam1' => 1],
                    'grid_render_parameters'       => ['testParam2' => 2],
                    'existing_entity_grid_id'      => 'id',
                    'create_form_route'            => 'test',
                    'create_enabled'               => true,
                    'create_acl'                   => 'acl',
                    'create_form_route_parameters' => ['name' => 'US'],
                ],
                true,
                true,
                [
                    'grid_name'                    => 'test',
                    'existing_entity_grid_id'      => 'id',
                    'create_form_route'            => 'test',
                    'create_enabled'               => true,
                    'create_form_route_parameters' => ['name' => 'US'],
                ]
            ],
            'create enabled route disabled'     => [
                [
                    'grid_name'                    => 'test',
                    'grid_parameters'              => ['testParam1' => 1],
                    'grid_render_parameters'       => ['testParam2' => 2],
                    'converter'                    => $converter,
                    'entity_class'                 => TestEntity::class,
                    'configs'                      => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'               => true,
                    'create_form_route'            => 'test',
                    'create_form_route_parameters' => ['name' => 'US'],
                ],
                [
                    'grid_name'                    => 'test',
                    'grid_parameters'              => ['testParam1' => 1],
                    'grid_render_parameters'       => ['testParam2' => 2],
                    'existing_entity_grid_id'      => 'id',
                    'create_form_route'            => 'test',
                    'create_enabled'               => false,
                    'create_form_route_parameters' => ['name' => 'US'],
                ],
                false,
                null,
                [
                    'grid_name'                    => 'test',
                    'existing_entity_grid_id'      => 'id',
                    'create_form_route'            => 'test',
                    'create_enabled'               => false,
                    'create_form_route_parameters' => ['name' => 'US'],
                ]
            ],
        ];
    }
}
