<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Tests\Unit\MockHelper;

class OroJquerySelect2HiddenTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    protected $factory;

    /**
     * @var OroJquerySelect2HiddenType
     */
    private $type;

    /**
     * @var SearchRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchRegistry;

    /**
     * @var SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchHandler;

    /**
     * @var ConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $converter;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var EntityToIdTransformer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityToIdTransformer;

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    protected function setUp()
    {
        parent::setUp();
        $this->type = $this->getMockBuilder('Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType')
            ->setMethods(['createDefaultTransformer'])
            ->setConstructorArgs(
                [
                    $this->getMockEntityManager(),
                    $this->getMockSearchRegistry(),
                    $this->getConfigProvider()
                ]
            )
            ->getMock();
    }

    protected function getExtensions()
    {
        return array_merge(parent::getExtensions(), [new TestFormExtension()]);
    }

    /**
     * @dataProvider bindDataProvider
     *
     * @param mixed $bindData
     * @param mixed $formData
     * @param mixed $viewData
     * @param array $options
     * @param array $expectedCalls
     * @param array $expectedVars
     */
    public function testBindData(
        $bindData,
        $formData,
        $viewData,
        array $options,
        array $expectedCalls,
        array $expectedVars
    ) {
        if (isset($options['converter'])
            && is_string($options['converter'])
            && method_exists($this, $options['converter'])
        ) {
            $options['converter'] = $this->{$options['converter']}();
        }

        foreach ($expectedCalls as $key => $calls) {
            $mock = $this->{'getMock' . ucfirst($key)}();
            MockHelper::addMockExpectedCalls($mock, $calls, $this);
        }

        $form = $this->factory->create($this->type, null, $options);

        $form->submit($bindData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());

        $view = $form->createView();
        $this->assertEquals($viewData, $view->vars['value']);

        foreach ($expectedVars as $name => $expectedValue) {
            $this->assertEquals($expectedValue, $view->vars[$name]);
        }
    }

    /**
     * Data provider for testBindData
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function bindDataProvider()
    {
        $entityId1 = $this->createMockEntity('id', 1);

        return [
            'use autocomplete_alias'     => [
                '1',
                $entityId1,
                '1',
                ['autocomplete_alias' => 'foo'],
                'expectedCalls' => [
                    'searchRegistry'        => [
                        ['getSearchHandler', ['foo'], 'getMockSearchHandler'],
                        ['getSearchHandler', ['foo'], 'getMockSearchHandler'],
                        ['getSearchHandler', ['foo'], 'getMockSearchHandler']
                    ],
                    'searchHandler'         => [
                        ['getProperties', [], ['bar', 'baz']],
                        ['getEntityName', [], 'TestEntityClass'],
                        [
                            'convertItem',
                            [$entityId1],
                            ['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']
                        ],
                    ],
                    'formType'              => [
                        ['createDefaultTransformer', ['TestEntityClass'], 'getMockEntityToIdTransformer']
                    ],
                    'entityToIdTransformer' => [
                        ['transform', [null], null],
                        ['reverseTransform', ['1'], $entityId1]
                    ]
                ],
                'expectedVars'  => [
                    'configs' => [
                        'placeholder'        => 'oro.form.choose_value',
                        'allowClear'         => 1,
                        'minimumInputLength' => 0,
                        'autocomplete_alias' => 'foo',
                        'properties'         => ['bar', 'baz'],
                        'route_name'         => 'oro_form_autocomplete_search',
                        'route_parameters'   => [],
                        'component'       => 'autocomplete'
                    ],
                    'attr'    => [
                        'data-selected-data' => json_encode(
                            [
                                ['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']
                            ]
                        )
                    ]
                ]
            ],
            'without autocomplete_alias' => [
                '1',
                $entityId1,
                '1',
                [
                    'configs'      => [
                        'route_name'       => 'custom_route',
                        'route_parameters' => ['test' => 'hello']
                    ],
                    'converter'    => 'getMockConverter',
                    'entity_class' => 'TestEntityClass'
                ],
                'expectedCalls' => [
                    'converter'             => [
                        [
                            'convertItem',
                            [$entityId1],
                            ['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']
                        ],
                    ],
                    'formType'              => [
                        ['createDefaultTransformer', ['TestEntityClass'], 'getMockEntityToIdTransformer']
                    ],
                    'entityToIdTransformer' => [
                        ['transform', [null], null],
                        ['reverseTransform', ['1'], $entityId1]
                    ]
                ],
                'expectedVars'  => [
                    'configs' => [
                        'placeholder'        => 'oro.form.choose_value',
                        'allowClear'         => 1,
                        'minimumInputLength' => 0,
                        'route_name'         => 'custom_route',
                        'route_parameters'   => ['test' => 'hello'],
                    ],
                    'attr'    => [
                        'data-selected-data' => json_encode(
                            [
                                ['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']
                            ]
                        )
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider createErrorsDataProvider
     *
     * @param array  $options
     * @param array  $expectedCalls
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     */
    public function testCreateErrors(
        array $options,
        array $expectedCalls,
        $expectedException,
        $expectedExceptionMessage
    ) {
        if (isset($options['converter'])
            && is_string($options['converter'])
            && method_exists($this, $options['converter'])
        ) {
            $options['converter'] = $this->{$options['converter']}();
        }

        foreach ($expectedCalls as $key => $calls) {
            $mock = $this->{'getMock' . ucfirst($key)}();
            MockHelper::addMockExpectedCalls($mock, $calls, $this);
        }

        $this->setExpectedException($expectedException, $expectedExceptionMessage);
        $this->factory->create($this->type, null, $options);
    }

    /**
     * Data provider for testBindData
     *
     * @return array
     */
    public function createErrorsDataProvider()
    {
        return [
            'configs.route_name must be set' => [
                [
                    'entity_class' => '\stdClass'
                ],
                'expectedCalls'            => [],
                'expectedException'        => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' => 'Option "configs[route_name]" must be set.'
            ],
            'converter must be set'          => [
                [
                    'entity_class' => '\stdClass',
                    'configs'      => [
                        'route_name' => 'foo'
                    ]
                ],
                'expectedCalls'            => [
                    'formType' => [
                        ['createDefaultTransformer', ['\stdClass'], 'getMockEntityToIdTransformer']
                    ],
                ],
                'expectedException'        => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' => 'The option "converter" must be set.'
            ],
            'converter invalid'              => [
                [
                    'entity_class' => '\stdClass',
                    'converter'    => 'bar',
                    'configs'      => [
                        'route_name' => 'foo'
                    ]
                ],
                'expectedCalls'            => [
                    'formType' => [
                        ['createDefaultTransformer', ['\stdClass'], 'getMockEntityToIdTransformer']
                    ],
                ],
                'expectedException'        => 'Symfony\Component\Form\Exception\UnexpectedTypeException',
                'expectedExceptionMessage' =>
                    'Expected argument of type "Oro\Bundle\FormBundle\Autocomplete\ConverterInterface", "string" given'
            ],
            'entity_class must be set'       => [
                [
                    'autocomplete_alias' => null,
                    'converter'          => 'getMockConverter',
                    'configs'            => [
                        'route_name' => 'foo'
                    ]
                ],
                'expectedCalls'            => [],
                'expectedException'        => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' => 'The option "entity_class" must be set.'
            ],
            'entity_class must be set2'      => [
                [
                    'converter'    => 'getMockConverter',
                    'entity_class' => 'bar',
                    'configs'      => [
                        'route_name' => 'foo'
                    ],
                    'transformer'  => 'invalid'
                ],
                'expectedCalls'            => [],
                'expectedException'        => 'Symfony\Component\Form\Exception\TransformationFailedException',
                'expectedExceptionMessage' =>
                    sprintf(
                        'The option "transformer" must be an instance of "%s".',
                        'Symfony\Component\Form\DataTransformerInterface'
                    )
            ]
        ];
    }

    public function testDefaultFormOptions()
    {
        $options = [
            'converter'    => $this->getMockConverter(),
            'entity_class' => '\stdClass',
            'configs'      => ['route_name' => 'custom']
        ];
        $this->type->expects($this->once())->method('createDefaultTransformer')
            ->will($this->returnValue($this->getMockEntityToIdTransformer()));

        $form = $this->factory->create($this->type, null, $options);

        $expectedOptions = [
            'error_bubbling' => false
        ];
        foreach ($expectedOptions as $optionName => $optionValue) {
            $this->assertSame($optionValue, $form->getConfig()->getOption($optionName));
        }
    }

    /**
     * Create mock entity by id property name and value
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockEntity($property, $value)
    {
        $getter = 'get' . ucfirst($property);
        $result = $this->getMock('MockEntity', [$getter]);
        $result->expects($this->any())->method($getter)->will($this->returnValue($value));

        return $result;
    }

    /**
     * @return EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->setMethods(['getClassMetadata', 'getRepository'])
                ->getMockForAbstractClass();
        }

        return $this->entityManager;
    }

    /**
     * @return SearchRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockSearchRegistry()
    {
        if (!$this->searchRegistry) {
            $this->searchRegistry = $this->getMockBuilder('Oro\Bundle\FormBundle\Autocomplete\SearchRegistry')
                ->disableOriginalConstructor()
                ->setMethods(['hasSearchHandler', 'getSearchHandler'])
                ->getMock();
        }

        return $this->searchRegistry;
    }

    /**
     * @return ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getConfigProvider()
    {
        if (!$this->configProvider) {
            $this->configProvider = $this
                ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
                ->disableOriginalConstructor()
                ->getMock();

            $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

            $this->configProvider
                ->expects($this->any())
                ->method('getConfig')
                ->will($this->returnValue($config));
        }

        return $this->configProvider;
    }

    /**
     * @return ConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockConverter()
    {
        if (!$this->converter) {
            $this->converter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');
        }

        return $this->converter;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockFormType()
    {
        return $this->type;
    }

    /**
     * @return SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockSearchHandler()
    {
        if (!$this->searchHandler) {
            $this->searchHandler = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface');
        }

        return $this->searchHandler;
    }

    /**
     * @return EntityToIdTransformer|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockEntityToIdTransformer()
    {
        if (!$this->entityToIdTransformer) {
            $this->entityToIdTransformer =
                $this->getMockBuilder('Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer')
                    ->disableOriginalConstructor()
                    ->setMethods(['transform', 'reverseTransform'])
                    ->getMockForAbstractClass();
        }

        return $this->entityToIdTransformer;
    }
}
