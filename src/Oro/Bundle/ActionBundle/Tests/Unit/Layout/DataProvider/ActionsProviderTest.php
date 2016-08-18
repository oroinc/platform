<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Layout\DataProvider\ActionsProvider;

class ActionsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OperationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $operationManager;

    /**
     * @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextHelper;

    /**
     * @var RestrictHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $restrictHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var ActionsProvider
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

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->restrictHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\RestrictHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->dataProvider = new ActionsProvider(
            $this->operationManager,
            $this->contextHelper,
            $this->restrictHelper,
            $this->translator
        );
    }

    public function testGetByGroup()
    {
        $groups = 'test';
        $expected = $this->assertGetByGroups($groups);
        $this->assertEquals($expected, $this->dataProvider->getByGroup(null, $groups));
    }

    public function testGetByGroupWithEntity()
    {
        $entity = new \stdClass();

        $actionContext = ['entityId' => null, 'entityClass' => '\stdClass'];

        $this->contextHelper->expects($this->once())
            ->method('getActionParameters')
            ->with(['entity' => $entity])
            ->willReturn($actionContext);

        $groups = 'test';
        $expected = $this->assertGetByGroups($groups, $actionContext);
        $this->assertEquals($expected, $this->dataProvider->getByGroup($entity, $groups));
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
     * @param string $operationName
     * @param string $label
     * @param bool $enabled
     * @param array $frontendOptions
     * @param array $buttonOptions
     * @param bool $hasForm
     * @return \PHPUnit_Framework_MockObject_MockObject|Operation
     */
    protected function getOperation(
        $operationName,
        $label,
        $enabled = true,
        array $frontendOptions = [],
        array $buttonOptions = [],
        $hasForm = false
    ) {
        $definition = new OperationDefinition();
        $definition->setEnabled($enabled);
        $definition->setName($operationName);
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
     * @param array $actionsContext
     * @return array
     */
    protected function assertGetByGroups($groups, array $actionsContext = null)
    {
        $actionOne = $this->getOperation('action1', 'action1_label');
        $actionTwo = $this->getOperation(
            'action2',
            'action2_label',
            true,
            ['title' => 'title', 'show_dialog' => true],
            ['icon' => 'icon'],
            true
        );
        $actionThree = $this->getOperation('action3', 'action3_label', false);
        $actions = [$actionOne, $actionTwo, $actionThree];

        $this->operationManager->expects($this->once())
            ->method('getOperations')
            ->with($actionsContext)
            ->will($this->returnValue($actions));
        $this->restrictHelper->expects($this->once())
            ->method('restrictOperationsByGroup')
            ->with($actions, $groups)
            ->will($this->returnArgument(0));
        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnArgument(0));

        $expected = [
            [
                'name' => 'action1',
                'label' => 'action1_label',
                'title' => 'action1_label',
                'icon' => '',
                'action' => $actionOne,
            ],
            [
                'name' => 'action2',
                'label' => 'action2_label',
                'title' => 'title',
                'icon' => 'icon',
                'action' => $actionTwo
            ]
        ];

        return $expected;
    }
}
