<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\Tree\AbstractNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\ResolvedFormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractProviderTest extends FormIntegrationTestCase
{
    protected const CONFIG_SCOPE = 'abstract';
    protected const TREE_NAME = 'abstract';

    /** @var FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formRegistry;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ChainSearchProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    abstract protected function getParentCheckboxLabel(): string;

    abstract public function getProvider(
        ConfigBag $configBag,
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        FormRegistryInterface $formRegistry,
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FeatureChecker $featureChecker,
        EventDispatcherInterface $eventDispatcher
    ): AbstractProvider;

    abstract protected function getFilePath(string $fileName): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(
                new DataBlockExtension()
            )
            ->getFormFactory();

        $this->formRegistry = $this->createMock(FormRegistryInterface::class);

        $formTYpe = $this->createMock(ResolvedFormType::class);

        $this->formRegistry->expects(self::any())
            ->method('getType')
            ->willReturn($formTYpe);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->searchProvider = $this->createMock(ChainSearchProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @dataProvider getApiTreeProvider
     */
    public function testGetApiTree(?string $path, SectionDefinition $expectedTree)
    {
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));

        self::assertEquals($expectedTree, $provider->getApiTree($path));
    }

    public function testGetApiTreeForUndefinedSection()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Config API section "undefined.sub_section" is not defined.');

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));

        $provider->getApiTree('undefined.sub_section');
    }

    public function getApiTreeProvider(): array
    {
        $root = new SectionDefinition('');
        $section1 = new SectionDefinition('section1');
        $root->addSubSection($section1);
        $section1->addVariable(new VariableDefinition('some_field', 'string'));
        $section1->addVariable(new VariableDefinition('some_api_only_field', 'integer'));
        $section11 = new SectionDefinition('section11');
        $section1->addSubSection($section11);
        $section11->addVariable(new VariableDefinition('some_another_field', 'string'));

        return [
            'root section' => [
                null,
                $root
            ],
            'top section' => [
                'section1',
                $section1
            ],
            'sub section' => [
                'section1/section11',
                $section11
            ],
        ];
    }

    public function testTreeProcessing()
    {
        $configManager = $this->createMock(ConfigManager::class);

        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturn(true);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(ConfigSettingsFormOptionsEvent::class),
                ConfigSettingsFormOptionsEvent::SET_OPTIONS
            )
            ->willReturnCallback(function (ConfigSettingsFormOptionsEvent $event) use ($configManager) {
                self::assertSame($configManager, $event->getConfigManager());

                $formOptions = $event->getFormOptions('some_field');
                $formOptions['value_hint'] = 'value hint';
                $event->setFormOptions('some_field', $formOptions);

                return $event;
            });

        // check good_definition.yml for further details
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $form = $provider->getForm('third_group', $configManager);
        self::assertInstanceOf(FormInterface::class, $form);

        // test that fields were added
        self::assertTrue($form->has('some_field'));
        self::assertTrue($form->has('some_another_field'));
        self::assertEquals(
            $this->getParentCheckboxLabel(),
            $form->get('some_field')->getConfig()->getOption('use_parent_field_label')
        );
        self::assertEquals(
            'value hint',
            $form->get('some_field')->getConfig()->getOption('value_hint')
        );
        self::assertEquals(
            $this->getParentCheckboxLabel(),
            $form->get('some_another_field')->getConfig()->getOption('use_parent_field_label')
        );

        // only needed fields were added
        self::assertCount(2, $form);
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptions(
        string $filename,
        string $exception,
        string $message,
        string $method,
        array $arguments
    ) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath($filename));
        call_user_func_array([$provider, $method], $arguments);
    }

    public function exceptionDataProvider(): array
    {
        return [
            'tree is not defined should trigger error' => [
                'filename'  => 'tree_is_not_defined.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => sprintf('Tree "%s" is not defined.', static::TREE_NAME),
                'method'    => 'getTree',
                'arguments' => []
            ],
            'tree is not defined get jsTree' => [
                'filename'  => 'tree_is_not_defined.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => sprintf('Tree "%s" is not defined.', static::TREE_NAME),
                'method'    => 'getJsTree',
                'arguments' => []
            ],
            'fields definition on bad tree level' => [
                'filename'  => 'bad_field_level_definition.yml',
                'exception' => \Exception::class,
                'message'   => 'Field "some_field" will not be ever rendered. Please check nesting level',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'trying to get not existing subtree' => [
                'filename'  => 'good_definition.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => 'Subtree "NOT_EXISTING_ONE" not found',
                'method'    => 'getSubtree',
                'arguments' => ['NOT_EXISTING_ONE']
            ],
            'bad field definition - no data_type' => [
                'filename'  => 'bad_field_without_data_type.yml',
                'exception' => InvalidConfigurationException::class,
                'message'   => 'The "data_type" is required except "ui_only" is defined. '
                    . '{"options":[],"page_reload":false}',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'bad field definition' => [
                'filename'  => 'bad_field_definition.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => 'Field "NOT_EXISTED_FIELD" is not defined.',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'bad field required without constraints' => [
                'filename'  => 'bad_field_required_without_constraints.yml',
                'exception' => InvalidConfigurationException::class,
                'message'   => 'Invalid configuration for path "system_configuration.fields.some_field": ' .
                    'The "constraints" option is required when field is required.',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'bad_field_not_required_with_not_blank_constraint' => [
                'filename'  => 'bad_field_not_required_with_not_blank_constraint.yml',
                'exception' => InvalidConfigurationException::class,
                'message'   => 'Invalid configuration for path "system_configuration.fields.some_field": ' .
                    'Field must be required when field has NotBlank constraint.',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'bad group definition' => [
                'filename'  => 'bad_group_definition.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => 'Group "NOT_EXITED_GROUP" is not defined.',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'bad - undefined field in api_tree' => [
                'filename'  => 'bad_undefined_field_in_api_tree.yml',
                'exception' => InvalidConfigurationException::class,
                'message'   => 'The field "some_field" is used in "system_configuration.section1.some_field",'
                    . ' but it is not defined in "fields" section.',
                'method'    => 'getTree',
                'arguments' => []
            ],
            'bad - ui_only field in api_tree' => [
                'filename'  => 'bad_ui_only_field_in_api_tree.yml',
                'exception' => InvalidConfigurationException::class,
                'message'   => 'The field "some_field" is used in "system_configuration.section1.some_field",'
                    . ' but "data_type" is not defined in "fields" section.',
                'method'    => 'getTree',
                'arguments' => []
            ],
        ];
    }

    public function testTreeProcessingWithACL()
    {
        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->withConsecutive(['ALLOWED'], ['DENIED'])
            ->willReturnOnConsecutiveCalls(true, false);

        // check good_definition_with_acl_check.yml for further details
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition_with_acl_check.yml'));
        $form = $provider->getForm('third_group', $this->createMock(ConfigManager::class));
        self::assertInstanceOf(FormInterface::class, $form);

        // test that fields were added
        self::assertTrue($form->has('some_field'));
        self::assertFalse($form->has('some_another_field'));

        // only needed fields were added
        self::assertCount(1, $form);
    }

    /**
     * @dataProvider activeGroupsDataProvider
     */
    public function testChooseActiveGroups(
        ?string $activeGroup,
        ?string $activeSubGroup,
        string $expectedGroup,
        string $expectedSubGroup
    ) {
        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        [$activeGroup, $activeSubGroup] = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);
        self::assertEquals($expectedGroup, $activeGroup);
        self::assertEquals($expectedSubGroup, $activeSubGroup);
    }

    public function activeGroupsDataProvider(): array
    {
        return [
            'check auto choosing both groups'  => [
                null,
                null,
                'horizontal tab name' => 'first_group',
                'vertical tab name'   => 'third_group'
            ],
            'check auto choosing sub group'    => [
                'first_group',
                null,
                'horizontal tab name' => 'first_group',
                'vertical tab name'   => 'third_group'
            ],
            'check not changing if all exists' => [
                'first_group',
                'another_branch_first',
                'horizontal tab name' => 'first_group',
                'vertical tab name'   => 'another_branch_first'
            ]
        ];
    }

    protected function getConfig(string $path): array
    {
        $config = Yaml::parse(file_get_contents($path));

        $processor = new ProcessorDecorator(
            new Processor(),
            ['some_field', 'some_another_field', 'some_ui_only_field', 'some_api_only_field']
        );

        return $processor->process($config);
    }

    protected function getProviderWithConfigLoaded(string $configPath): AbstractProvider
    {
        $config = $this->getConfig($configPath);
        $container = $this->createMock(ContainerBuilder::class);

        $configBag = new ConfigBag($config, $container);

        return $this->getProvider(
            $configBag,
            $this->translator,
            $this->factory,
            $this->formRegistry,
            $this->authorizationChecker,
            $this->searchProvider,
            $this->featureChecker,
            $this->eventDispatcher
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new FormType(
                        new ConfigSubscriber($this->createMock(ConfigManager::class)),
                        $this->createMock(ContainerInterface::class)
                    ),
                    new FormFieldType(),
                    new ParentScopeCheckbox()
                ],
                []
            ),
        ];
    }

    /**
     * @dataProvider featuresCheckDataProvider
     */
    public function testGetFilteredTree(string $disabledNode, array $expected)
    {
        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturnCallback(function ($resource) use ($disabledNode) {
                return $resource !== $disabledNode;
            });

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $tree = $this->getNodeNamesTree($provider->getTree());
        self::assertEqualsCanonicalizing($expected, $tree);
    }

    public function testGetJsTree()
    {
        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));

        $this->searchProvider->expects(self::any())
            ->method('supports')
            ->willReturn(true);

        $this->searchProvider->expects(self::exactly(4))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(
                ['Third group'],
                ['Fourth group'],
                ['title some field', 'tooltip some field'],
                ['title some other field'],
            );

        $result = $provider->getJsTree();
        $expected = [
            [
                'id' => 'third_group',
                'text' => 'Third group',
                'icon' => 'fa-file',
                'parent' => 'second_group',
                'priority' => 254,
                'search_by' => [
                    'Third group',
                    'Fourth group',
                    'title some field',
                    'tooltip some field',
                    'title some other field',
                ]
            ],
            [
                'id' => 'second_group',
                'text' => 'Second group',
                'icon' => 'fa-file',
                'parent' => 'first_group',
                'priority' => 0,
                'search_by' => ['Second group']
            ],
            [
                'id' => 'first_group',
                'text' => 'First group',
                'icon' => 'fa-file',
                'parent' => '#',
                'priority' => 0,
                'search_by' => ['First group']
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function featuresCheckDataProvider(): array
    {
        return [
            [
                'another_branch_first',
                [
                    'system_configuration' => [
                        'children' => [
                            'first_group' => [
                                'children' => [
                                    'second_group' => [
                                        'children' => [
                                            'third_group' => [
                                                'children' => [
                                                    'fourth_group' => [
                                                        'children' => [
                                                            'some_another_field',
                                                            'some_field',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'some_another_field',
                [
                    'system_configuration' => [
                        'children' => [
                            'first_group' => [
                                'children' => [
                                    'second_group' => [
                                        'children' => [
                                            'third_group' => [
                                                'children' => [
                                                    'fourth_group' => [
                                                        'children' => [
                                                            'some_field',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'another_branch_first' => [
                                                'children' => [
                                                    'another_branch_second' => [
                                                        'children' => [],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getNodeNamesTree(AbstractNodeDefinition $node): array
    {
        $name = $node->getName();

        $result = [];
        if ($node instanceof GroupNodeDefinition) {
            $result[$name]['children'] = [];
            foreach ($node->getIterator() as $childNode) {
                $result[$name]['children'] = array_merge(
                    $result[$name]['children'],
                    $this->getNodeNamesTree($childNode)
                );
            }

            return $result;
        }

        return [$node->getName()];
    }
}
