<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\Tree\AbstractNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
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
    protected const CONFIG_NAME = 'system_configuration';

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ChainSearchProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchProvider;

    /** @var FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formRegistry;

    abstract public function getParentCheckboxLabel(): string;

    abstract public function getProvider(
        ConfigBag $configBag,
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FormRegistryInterface $formRegistry
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

        $this->formRegistry->expects($this->any())
            ->method('getType')
            ->willReturn($formTYpe);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->searchProvider = $this->createMock(ChainSearchProvider::class);
    }

    /**
     * @dataProvider getApiTreeProvider
     */
    public function testGetApiTree(?string $path, SectionDefinition $expectedTree)
    {
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));

        $this->assertEquals(
            $expectedTree,
            $provider->getApiTree($path)
        );
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
        // check good_definition.yml for further details
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $form = $provider->getForm('third_group');
        $this->assertInstanceOf(FormInterface::class, $form);

        // test that fields were added
        $this->assertTrue($form->has('some_field'));
        $this->assertTrue($form->has('some_another_field'));
        $this->assertEquals(
            $this->getParentCheckboxLabel(),
            $form->get('some_field')->getConfig()->getOption('parent_checkbox_label')
        );
        $this->assertEquals(
            $this->getParentCheckboxLabel(),
            $form->get('some_another_field')->getConfig()->getOption('parent_checkbox_label')
        );

        // only needed fields were added
        $this->assertCount(2, $form);
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
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath($filename));
        call_user_func_array([$provider, $method], $arguments);
    }

    public function exceptionDataProvider(): array
    {
        return [
            'tree is not defined should trigger error' => [
                'filename'  => 'tree_is_not_defined.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => sprintf('Tree "%s" is not defined.', static::CONFIG_NAME),
                'method'    => 'getTree',
                'arguments' => []
            ],
            'tree is not defined get jsTree' => [
                'filename'  => 'tree_is_not_defined.yml',
                'exception' => ItemNotFoundException::class,
                'message'   => sprintf('Tree "%s" is not defined.', static::CONFIG_NAME),
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
        // check good_definition_with_acl_check.yml for further details
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition_with_acl_check.yml'));

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(['ALLOWED'], ['DENIED'])
            ->willReturnOnConsecutiveCalls(true, false);

        $form = $provider->getForm('third_group');
        $this->assertInstanceOf(FormInterface::class, $form);

        // test that fields were added
        $this->assertTrue($form->has('some_field'));
        $this->assertFalse($form->has('some_another_field'));

        // only needed fields were added
        $this->assertCount(1, $form);
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
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        [$activeGroup, $activeSubGroup] = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);
        $this->assertEquals($expectedGroup, $activeGroup);
        $this->assertEquals($expectedSubGroup, $activeSubGroup);
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
            $this->authorizationChecker,
            $this->searchProvider,
            $this->formRegistry
        );
    }

    public function getExtensions()
    {
        $subscriber = $this->getMockBuilder(ConfigSubscriber::class)
            ->onlyMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();
        $container = $this->createMock(ContainerInterface::class);

        $formType = new FormType($subscriber, $container);
        $formFieldType = new FormFieldType();
        $useParentScope = new ParentScopeCheckbox();

        return [
            new PreloadedExtension(
                [
                    FormType::class => $formType,
                    FormFieldType::class => $formFieldType,
                    ParentScopeCheckbox::class => $useParentScope
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
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturnCallback(function ($resource) use ($disabledNode) {
                return $resource !== $disabledNode;
            });

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $provider->setFeatureChecker($featureChecker);
        $tree = $this->getNodeNamesTree($provider->getTree());
        $this->assertEqualsCanonicalizing($expected, $tree);
    }

    public function testGetJsTree()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $provider->setFeatureChecker($featureChecker);

        $this->searchProvider->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $this->searchProvider->expects($this->exactly(6))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(
                ['Third group'],
                ['Fourth group'],
                ['title some field', 'tooltip some field'],
                ['title some other field'],
                ['Another branch first group'],
                ['Another branch second group']
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
                'id' => 'another_branch_first',
                'text' => 'Another branch first group',
                'icon' => 'fa-file',
                'parent' => 'second_group',
                'priority' => 0,
                'search_by' => [
                    'Another branch first group',
                    'Another branch second group',
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

        $this->assertEquals($expected, $result);
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
        $result = [];
        if ($node instanceof GroupNodeDefinition) {
            $result[$node->getName()]['children'] = [];
            foreach ($node->getIterator() as $childNode) {
                $result[$node->getName()]['children'] = array_merge(
                    $result[$node->getName()]['children'],
                    $this->getNodeNamesTree($childNode)
                );
            }

            return $result;
        }

        return [$node->getName()];
    }
}
