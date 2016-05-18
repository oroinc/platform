<?php
namespace ConfigBundle\Tests\Provider;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;

class SystemConfigurationFormProviderTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(
                new DataBlockExtension()
            )
            ->getFormFactory();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->securityFacade);
    }

    /**
     * @dataProvider getApiTreeProvider
     */
    public function testGetApiTree($path, $expectedTree)
    {
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');

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
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');

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
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');
        $form     = $provider->getForm('third_group');
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);

        // test that fields were added
        $this->assertTrue($form->has('some_field'));
        $this->assertTrue($form->has('some_another_field'));

        // only needed fields were added
        $this->assertCount(2, $form);
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptions($filename, $exception, $message, $method, $arguments)
    {
        $this->setExpectedException($exception, $message);
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/' . $filename);
        call_user_func_array(array($provider, $method), $arguments);
    }

    /**
     * @return array
     */
    public function exceptionDataProvider()
    {
        return array(
            'tree is not defined should trigger error' => array(
                'filename'  => 'tree_does_not_defined.yml',
                'exception' => '\Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException',
                'message'   => 'Tree "system_configuration" is not defined.',
                'method'    => 'getTree',
                'arguments' => array()
            ),
            'fields definition on bad tree level'      => array(
                'filename'  => 'bad_field_level_definition.yml',
                'exception' => '\Exception',
                'message'   => 'Field "some_field" will not be ever rendered. Please check nesting level',
                'method'    => 'getTree',
                'arguments' => array()
            ),
            'trying to get not existing subtree'       => array(
                'filename'  => 'good_definition.yml',
                'exception' => '\Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException',
                'message'   => 'Subtree "NOT_EXISTING_ONE" not found',
                'method'    => 'getSubtree',
                'arguments' => array('NOT_EXISTING_ONE')
            ),
            'bad field definition - no data_type'      => array(
                'filename'  => 'bad_field_without_data_type.yml',
                'exception' => '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
                'message'   => 'The "data_type" is required except "ui_only" is defined. {"options":[]}',
                'method'    => 'getTree',
                'arguments' => array()
            ),
            'bad field definition'                     => array(
                'filename'  => 'bad_field_definition.yml',
                'exception' => '\Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException',
                'message'   => 'Field "NOT_EXISTED_FIELD" is not defined.',
                'method'    => 'getTree',
                'arguments' => array()
            ),
            'bad group definition'                     => array(
                'filename'  => 'bad_group_definition.yml',
                'exception' => '\Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException',
                'message'   => 'Group "NOT_EXITED_GROUP" is not defined.',
                'method'    => 'getTree',
                'arguments' => array()
            ),
            'bad - undefined field in api_tree'        => array(
                'filename'  => 'bad_undefined_field_in_api_tree.yml',
                'exception' => '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
                'message'   => 'The field "some_field" is used in "oro_system_configuration.section1.some_field",'
                    . ' but it is not defined in "fields" section.',
                'method'    => 'getTree',
                'arguments' => array()
            ),
            'bad - ui_only field in api_tree'          => array(
                'filename'  => 'bad_ui_only_field_in_api_tree.yml',
                'exception' => '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
                'message'   => 'The field "some_field" is used in "oro_system_configuration.section1.some_field",'
                    . ' but "data_type" is not defined in "fields" section.',
                'method'    => 'getTree',
                'arguments' => array()
            ),
        );
    }

    public function testTreeProcessingWithACL()
    {
        // check good_definition_with_acl_check.yml for further details
        $provider = $this->getProviderWithConfigLoaded(
            __DIR__ . '/../Fixtures/Provider/good_definition_with_acl_check.yml'
        );

        $this->securityFacade->expects($this->at(0))->method('isGranted')->with($this->equalTo('ALLOWED'))
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->at(1))->method('isGranted')->with($this->equalTo('DENIED'))
            ->will($this->returnValue(false));

        $form = $provider->getForm('third_group');
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);

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
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');
        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);
        $this->assertEquals($expectedGroup, $activeGroup);
        $this->assertEquals($expectedSubGroup, $activeSubGroup);
    }

    public function activeGroupsDataProvider()
    {
        return array(
            'check auto choosing both groups'  => array(
                null,
                null,
                'horizontal tab name' => 'first_group',
                'vertical tab name'   => 'third_group'
            ),
            'check auto choosing sub group'    => array(
                'first_group',
                null,
                'horizontal tab name' => 'first_group',
                'vertical tab name'   => 'third_group'
            ),
            'check not changing if all exists' => array(
                'first_group',
                'another_branch_first',
                'horizontal tab name' => 'first_group',
                'vertical tab name'   => 'another_branch_first'
            )
        );
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
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();
        $configBag = new ConfigBag($config, $container);
        $provider = new SystemConfigurationFormProvider($configBag, $this->factory, $this->securityFacade);

        return $provider;
    }

    public function getExtensions()
    {
        $subscriber = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber')
            ->setMethods(array('__construct'))
            ->disableOriginalConstructor()->getMock();

        $formType       = new FormType($subscriber);
        $formFieldType  = new FormFieldType();
        $useParentScope = new ParentScopeCheckbox();

        return array(
            new PreloadedExtension(
                array(
                    $formType->getName()       => $formType,
                    $formFieldType->getName()  => $formFieldType,
                    $useParentScope->getName() => $useParentScope
                ),
                array()
            ),
        );
    }
}
