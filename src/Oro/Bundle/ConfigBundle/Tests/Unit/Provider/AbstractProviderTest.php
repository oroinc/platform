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
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
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
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractProviderTest extends FormIntegrationTestCase
{
    const CONFIG_NAME = 'system_configuration';

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ChainSearchProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchProvider;

    /** @var  FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formRegistry;

    /**
     * Get parent checkbox label for test
     *
     * @return string
     */
    abstract public function getParentCheckboxLabel();

    /**
     * @param ConfigBag $configBag
     * @param TranslatorInterface $translator
     * @param FormFactoryInterface $formFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ChainSearchProvider $searchProvider
     * @param FormRegistryInterface $formRegistry
     *
     * @return AbstractProvider
     */
    abstract public function getProvider(
        ConfigBag $configBag,
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FormRegistryInterface $formRegistry
    );

    /**
     * Return correct path to fileName
     *
     * @param string $fileName
     *
     * @return string
     */
    abstract protected function getFilePath($fileName);

    protected function setUp()
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
            ->will($this->returnArgument(0));

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->searchProvider = $this->createMock(ChainSearchProvider::class);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->authorizationChecker, $this->translator);
    }

    /**
     * @dataProvider getApiTreeProvider
     *
     * @param string $path
     * @param array $expectedTree
     */
    public function testGetApiTree($path, $expectedTree)
    {
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));

        $this->assertEquals(
            $expectedTree,
            $provider->getApiTree($path)
        );
    }

    /**
     * @expectedException \Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException
     * @expectedExceptionMessage Config API section "undefined.sub_section" is not defined.
     */
    public function testGetApiTreeForUndefinedSection()
    {
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));

        $provider->getApiTree('undefined.sub_section');
    }

    /**
     * @return array
     */
    public function getApiTreeProvider()
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
        $form     = $provider->getForm('third_group');
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
     *
     * @param string $filename
     * @param string $exception
     * @param string $message
     * @param string $method
     * @param array $arguments
     */
    public function testExceptions($filename, $exception, $message, $method, $arguments)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath($filename));
        call_user_func_array([$provider, $method], $arguments);
    }

    /**
     * @return array
     */
    public function exceptionDataProvider()
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

        $this->authorizationChecker->expects($this->at(0))->method('isGranted')->with($this->equalTo('ALLOWED'))
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->at(1))->method('isGranted')->with($this->equalTo('DENIED'))
            ->will($this->returnValue(false));

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
     *
     * @param string $activeGroup
     * @param string $activeSubGroup
     * @param string $expectedGroup
     * @param string $expectedSubGroup
     */
    public function testChooseActiveGroups($activeGroup, $activeSubGroup, $expectedGroup, $expectedSubGroup)
    {
        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);
        $this->assertEquals($expectedGroup, $activeGroup);
        $this->assertEquals($expectedSubGroup, $activeSubGroup);
    }

    /**
     * @return array
     */
    public function activeGroupsDataProvider()
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

    /**
     * Parse config fixture and validate through processorDecorator
     *
     * @param string $path
     *
     * @return array
     */
    protected function getConfig($path)
    {
        $config = Yaml::parse(file_get_contents($path));

        $processor = new ProcessorDecorator(
            new Processor(),
            ['some_field', 'some_another_field', 'some_ui_only_field', 'some_api_only_field']
        );

        return $processor->process($config);
    }

    /**
     * @param string $configPath
     *
     * @return SystemConfigurationFormProvider
     */
    protected function getProviderWithConfigLoaded($configPath)
    {
        $config   = $this->getConfig($configPath);
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configBag = new ConfigBag($config, $container);

        $provider = $this->getProvider(
            $configBag,
            $this->translator,
            $this->factory,
            $this->authorizationChecker,
            $this->searchProvider,
            $this->formRegistry
        );

        return $provider;
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        $subscriber = $this->getMockBuilder(ConfigSubscriber::class)
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()->getMock();
        $container = $this->createMock(ContainerInterface::class);

        $formType       = new FormType($subscriber, $container);
        $formFieldType  = new FormFieldType();
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
     * @param string $disabledNode
     * @param array $expected
     */
    public function testGetFilteredTree($disabledNode, array $expected)
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturnCallback(
                function ($resource) use ($disabledNode) {
                    return $resource !== $disabledNode;
                }
            );

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $provider->setFeatureChecker($featureChecker);
        $tree = $this->getNodeNamesTree($provider->getTree());
        $this->assertEquals($expected, $tree, '', 0.0, 10, true);
    }

    public function testGetJsTree()
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturnSelf();

        $provider = $this->getProviderWithConfigLoaded($this->getFilePath('good_definition.yml'));
        $provider->setFeatureChecker($featureChecker);

        $this->searchProvider->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $this->searchProvider
            ->expects($this->exactly(6))
            ->method('getData')
            ->willReturn(
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

    /**
     * @return array
     */
    public function featuresCheckDataProvider()
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

    /**
     * @param AbstractNodeDefinition|GroupNodeDefinition $node
     * @return array
     */
    protected function getNodeNamesTree(AbstractNodeDefinition $node)
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
