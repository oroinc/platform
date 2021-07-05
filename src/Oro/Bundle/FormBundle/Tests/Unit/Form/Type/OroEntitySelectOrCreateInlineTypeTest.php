<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OroEntitySelectOrCreateInlineTypeTest extends FormIntegrationTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var OroEntitySelectOrCreateInlineType|\PHPUnit\Framework\MockObject\MockObject */
    private $formType;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);

        $configManager = $this->createMock(ConfigManager::class);
        $provider = $this->createMock(ConfigProvider::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturn($provider);

        $provider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);
        $entityToIdTransformer = $this->createMock(EntityToIdTransformer::class);

        $this->formType = $this->getMockBuilder(OroEntitySelectOrCreateInlineType::class)
            ->onlyMethods(['createDefaultTransformer'])
            ->setConstructorArgs([
                $this->authorizationChecker,
                $configManager,
                $entityManager,
                $searchRegistry
            ])
            ->getMock();

        $this->formType->expects($this->any())
            ->method('createDefaultTransformer')
            ->willReturn($entityToIdTransformer);

        parent::setUp();
    }

    protected function getExtensions()
    {
        $entityManager = $this->createMock(EntityManager::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $handler = $this->createMock(SearchHandlerInterface::class);
        $searchRegistry = $this->createMock(SearchRegistry::class);

        $handler->expects($this->any())
            ->method('getProperties')
            ->willReturn([]);

        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->willReturn($handler);

        $configProvider = $this->createMock(ConfigProvider::class);
        $config = $this->createMock(ConfigInterface::class);

        $configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects($this->any())
            ->method('get')
            ->willReturn('value');

        return [
            new PreloadedExtension(
                [
                    OroEntitySelectOrCreateInlineType::class => $this->formType
                ],
                []
            ),
            new EntitySelectOrCreateInlineFormExtension(
                $entityManager,
                $searchRegistry,
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
        bool $aclAllowed,
        bool $aclExpectedToCall,
        array $expectedViewVars = []
    ) {
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($argument) use ($inputOptions) {
                return $inputOptions[$argument] ?? null;
            });

        if ($aclExpectedToCall) {
            if (!empty($expectedOptions['create_acl'])) {
                $this->authorizationChecker->expects($this->any())
                    ->method('isGranted')
                    ->with($expectedOptions['create_acl'])
                    ->willReturn($aclAllowed);
            } else {
                $this->authorizationChecker->expects($this->any())
                    ->method('isGranted')
                    ->with('CREATE', 'Entity:Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity')
                    ->willReturn($aclAllowed);
            }
        } else {
            $this->authorizationChecker->expects($this->any())
                ->method('isGranted');
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
            'create disabled'                   => [
                [
                    'grid_widget_route' => 'some_route',
                    'grid_name'      => 'test',
                    'converter'      => $converter,
                    'entity_class'   => TestEntity::class,
                    'configs'        => [
                        'route_name' => 'test'
                    ],
                    'create_enabled' => false
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ],
                false,
                false,
                [
                    'grid_widget_route' => 'some_route',
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
                false,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ]
            ],
            'create has route disabled'         => [
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
                false,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ]
            ],
            'create enabled acl disallowed'     => [
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
                false,
                true,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ]
            ],
            'create enabled acl allowed'        => [
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
            'create enabled acl allowed custom' => [
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
        ];
    }
}
