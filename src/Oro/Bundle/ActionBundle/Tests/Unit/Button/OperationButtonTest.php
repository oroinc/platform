<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OperationButtonTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    protected $originOperationName;

    /** @var Operation|\PHPUnit\Framework\MockObject\MockObject */
    protected $operation;

    /** @var OperationDefinition|\PHPUnit\Framework\MockObject\MockObject */
    protected $definition;

    /** @var OperationButton */
    protected $button;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->originOperationName = 'origin_name';
        $this->definition = $this->createMock(OperationDefinition::class);
        $this->operation = $this->getOperation($this->definition);

        $this->button = new OperationButton(
            $this->originOperationName,
            $this->operation,
            new ButtonContext(),
            new ActionData()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->operation, $this->button);
    }

    public function testGetName()
    {
        $this->assertEquals($this->originOperationName, $this->button->getName());
    }

    public function testGetLabel()
    {
        $label = 'test_label';
        $this->definition->expects($this->once())->method('getLabel')->willReturn($label);

        $this->assertEquals($label, $this->button->getLabel());
    }

    public function testGetIcon()
    {
        $this->assertNull($this->button->getIcon());

        $icon = 'test-icon';
        $this->definition->expects($this->once())->method('getButtonOptions')->willReturn(['icon' => $icon]);

        $this->assertEquals($icon, $this->button->getIcon());
    }

    public function testGetOrder()
    {
        $order = mt_rand(10, 100);
        $this->definition->expects($this->once())->method('getOrder')->willReturn($order);

        $this->assertEquals($order, $this->button->getOrder());
    }

    public function testGetTemplate()
    {
        $this->assertEquals(OperationButton::DEFAULT_TEMPLATE, $this->button->getTemplate());
    }

    public function testGetTemplateData()
    {
        $defaultData = [
            'params' => $this->operation->getDefinition(),
            'actionData' => new ActionData(),
            'frontendOptions' => null,
            'buttonOptions' => null,
            'hasForm' => null,
            'showDialog' => false,
            'routeParams' => [
                'operationName' => $this->operation->getDefinition()->getName(),
                'entityClass' => null,
                'entityId' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,

            ],
            'executionRoute' => null,
            'dialogRoute' => null,
            'additionalData' => [],
            'aClass' => '',
            'jsDialogWidget' => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
        ];

        $templateData = $this->button->getTemplateData();
        $this->assertEquals($defaultData, $templateData);
    }

    public function testGetButtonContext()
    {
        $this->assertInstanceOf(ButtonContext::class, $this->button->getButtonContext());
    }

    public function testGetTemplateWithConfiguredFrontendOptions()
    {
        $templateName = uniqid('test_template', true);
        $this->definition->expects($this->once())->method('getButtonOptions')->willReturn(
            [OperationButton::BUTTON_TEMPLATE_KEY => $templateName]
        );

        $this->assertEquals($templateName, $this->button->getTemplate());
    }

    public function testGetOperation()
    {
        $this->assertEquals($this->operation, $this->button->getOperation());
    }

    public function testSetData()
    {
        $newData = new ActionData(['test_field' => 'test value']);
        $this->assertNotEquals($newData, $this->button->getTemplateData()['actionData']);
        $this->button->setData($newData);
        $this->assertEquals($newData, $this->button->getTemplateData()['actionData']);
    }

    public function testGetTranslationDomain()
    {
        $this->assertNull($this->button->getTranslationDomain());
    }

    public function testClone()
    {
        $button = new OperationButton(
            $this->originOperationName,
            $this->operation,
            new ButtonContext(),
            new ActionData()
        );

        $newButton = clone $button;

        $this->assertEquals($newButton->getButtonContext(), $this->button->getButtonContext());
        $this->assertNotSame($newButton->getButtonContext(), $this->button->getButtonContext());

        $this->assertEquals($newButton->getOperation(), $this->button->getOperation());
        $this->assertNotSame($newButton->getOperation(), $this->button->getOperation());
    }

    /**
     * @param OperationDefinition $definition
     * @return Operation
     */
    private function getOperation(OperationDefinition $definition)
    {
        return new Operation(
            $this->createMock(ActionFactoryInterface::class),
            $this->getMockBuilder(ExpressionFactory::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AttributeAssembler::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(FormOptionsAssembler::class)->disableOriginalConstructor()->getMock(),
            $definition
        );
    }
}
