<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Layout\DataProvider\ActionsDataProvider;

use Oro\Component\Layout\ContextInterface;

class ActionsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OperationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $operationManager;

    /**
     * @var RestrictHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $restrictHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextHelper;

    /**
     * @var ActionsDataProvider
     */
    protected $dataProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->restrictHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\RestrictHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new ActionsDataProvider(
            $this->operationManager,
            $this->restrictHelper,
            $this->translator,
            $this->contextHelper
        );
    }

    public function testGetData()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $this->assertSame($this->dataProvider, $this->dataProvider->getData($context));
    }

    public function testGetByGroup()
    {
        $groups = 'test';
        $expected = $this->assertGetByGroups($groups);
        $this->assertEquals($expected, $this->dataProvider->getByGroup($groups));
    }

    public function testGetAll()
    {
        $expected = $this->assertGetByGroups(null);
        $this->assertEquals($expected, $this->dataProvider->getAll());
    }

    public function testGetWithoutGroup()
    {
        $expected = $this->assertGetByGroups(false);
        $this->assertEquals($expected, $this->dataProvider->getAll());
    }

    /**
     * @dataProvider propertyDataProvider
     * @param string $property
     * @param array $expectedGroups
     */
    public function testMagicGet($property, array $expectedGroups)
    {
        $expected = $this->assertGetByGroups($expectedGroups);
        $this->assertEquals($expected, $this->dataProvider->$property);
    }

    /**
     * @return array
     */
    public function propertyDataProvider()
    {
        return [
            [
                'groupOne',
                ['one']
            ],
            [
                'groupOneAndSecondGroup',
                ['one', 'second_group']
            ]
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Property someProperty is unknown
     */
    public function testMagicGetUnsupported()
    {
        $this->dataProvider->someProperty;
    }

    /**
     * @param string $actionName
     * @param string $label
     * @param bool $enabled
     * @param array $frontendOptions
     * @param array $buttonOptions
     * @param bool $hasForm
     * @return \PHPUnit_Framework_MockObject_MockObject|Operation
     */
    protected function getAction(
        $actionName,
        $label,
        $enabled = true,
        array $frontendOptions = [],
        array $buttonOptions = [],
        $hasForm = false
    ) {
        $definition = new OperationDefinition();
        $definition->setEnabled($enabled);
        $definition->setName($actionName);
        $definition->setLabel($label);
        $definition->setFrontendOptions($frontendOptions);
        $definition->setButtonOptions($buttonOptions);

        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $operation->expects($this->any())
            ->method('hasForm')
            ->will($this->returnValue($hasForm));

        return $operation;
    }

    /**
     * @param mixed $groups
     * @return array
     */
    protected function assertGetByGroups($groups)
    {
        $actionOne = $this->getAction('action1', 'action1_label');
        $actionTwo = $this->getAction(
            'action2',
            'action2_label',
            true,
            ['title' => 'title', 'show_dialog' => true],
            ['icon' => 'icon'],
            true
        );
        $actionThree = $this->getAction('action3', 'action3_label', false);
        $actions = [$actionOne, $actionTwo, $actionThree];

        $actionData = new ActionData(['key1' => 'val1']);

        $this->operationManager->expects($this->once())
            ->method('getActions')
            ->will($this->returnValue($actions));
        $this->restrictHelper->expects($this->once())
            ->method('restrictActionsByGroup')
            ->with($actions, $groups)
            ->will($this->returnArgument(0));
        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnArgument(0));
        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->willReturn($actionData);

        $expected = [
            [
                'name' => 'action1',
                'label' => 'action1_label',
                'title' => 'action1_label',
                'hasDialog' => false,
                'showDialog' => false,
                'icon' => '',
                'buttonOptions' => [],
                'frontendOptions' => [],
                'translates' => ['key1' => 'val1'],
            ],
            [
                'name' => 'action2',
                'label' => 'action2_label',
                'title' => 'title',
                'hasDialog' => true,
                'showDialog' => true,
                'icon' => 'icon',
                'buttonOptions' => ['icon' => 'icon'],
                'frontendOptions' => ['title' => 'title', 'show_dialog' => true],
                'translates' => ['key1' => 'val1'],
            ]
        ];

        return $expected;
    }
}
