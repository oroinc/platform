<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

class OperationButtonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $operation;

    /**
     * @var OperationDefinition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $definition;

    /**
     * @var ButtonContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $buttonContext;

    /**
     * @var OperationButton
     */
    protected $button;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {

        $this->definition = $this->getMockBuilder(OperationDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation->expects($this->any())->method('getDefinition')->willReturn($this->definition);

        $this->buttonContext = $this->getMockBuilder(ButtonContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->button = new OperationButton($this->operation, $this->buttonContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->operation, $this->definition, $this->button, $this->buttonContext);
    }

    public function testGetOrder()
    {
        $this->definition->expects($this->once())->method('getOrder')->willReturn(1);
        $this->assertEquals(1, $this->button->getOrder());
    }

    public function testGetTemplate()
    {
        //TODO: Must be updated https://magecore.atlassian.net/browse/BAP-12480
        $this->assertEquals(OperationButton::DEFAULT_TEMPLATE, $this->button->getTemplate());
    }

    public function testGetTemplateWithConfiguredFrontendOptions()
    {
        $templateName = uniqid();
        $this->definition->expects($this->atLeastOnce())->method('getFrontendOptions')->willReturn(
            [OperationButton::FRONTEND_TEMPLATE_KEY => $templateName]
        );
        $this->assertEquals($templateName, $this->button->getTemplate());
    }

    public function testGetTemplateData()
    {
        //TODO: Must be updated https://magecore.atlassian.net/browse/BAP-12480
        $this->assertInternalType('array', $this->button->getTemplateData());
    }

    public function testGetButtonContext()
    {
        $this->assertInstanceOf(ButtonContext::class, $this->button->getButtonContext());
    }
}
