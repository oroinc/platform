<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Symfony\Component\Form\FormRegistryInterface;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Model\PageFormConfigurationAssembler;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class PageFormConfigurationAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PageFormConfigurationAssembler */
    protected $assembler;

    /** @var  FormRegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formRegistry;

    /** @var  FormHandlerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $formHandlerRegistry;

    /** @var  FormTemplateDataProviderRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $formTemplateDataProviderRegistry;

    /** @var array */
    protected static $transitionConfiguration = [
        'form_type' => 'CustomFormType',
        WorkflowConfiguration::NODE_PAGE_FORM_CONFIGURATION => [
            'handler' => 'CustomFormHandler',
            'data_provider' => 'CustomFormDataProvider',
            'data_attribute' => 'CustomFormDataAttribute',
            'template' => 'CustomTemplate'
        ],
    ];

    protected function setUp()
    {
        $this->formRegistry = $this->getMockBuilder(FormRegistryInterface::class)
            ->getMockForAbstractClass();

        $this->formHandlerRegistry = $this->getMockBuilder(FormHandlerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formTemplateDataProviderRegistry = $this->getMockBuilder(FormTemplateDataProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assembler = new PageFormConfigurationAssembler(
            $this->formRegistry,
            $this->formHandlerRegistry,
            $this->formTemplateDataProviderRegistry
        );
    }

    public function testAssembleRequiredFormTypeException()
    {
        $expectedExceptionMessage = sprintf(
            'Unable to resolve form type "%s"',
            self::$transitionConfiguration['form_type']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $transition = $this->createMock(Transition::class);
        $this->assertTransitionMethodsCalled($transition, false);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(false);
        $this->assembler->assemble(self::$transitionConfiguration, $transition);
    }

    public function testAssembleRequiredHandlerException()
    {
        $expectedExceptionMessage = sprintf(
            'Unable to resolve form handler with alias "%s"',
            self::$transitionConfiguration[WorkflowConfiguration::NODE_PAGE_FORM_CONFIGURATION]['handler']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $transition = $this->createMock(Transition::class);
        $this->assertTransitionMethodsCalled($transition, false);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(true);
        $this->formHandlerRegistry->expects($this->once())->method('has')->willReturn(false);
        $this->assembler->assemble(self::$transitionConfiguration, $transition);
    }

    public function testAssembleRequiredDataProviderException()
    {
        $expectedExceptionMessage = sprintf(
            'Unable to resolve form data provider with alias "%s"',
            self::$transitionConfiguration[WorkflowConfiguration::NODE_PAGE_FORM_CONFIGURATION]['data_provider']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $transition = $this->createMock(Transition::class);
        $this->assertTransitionMethodsCalled($transition, false);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(true);
        $this->formHandlerRegistry->expects($this->once())->method('has')->willReturn(true);
        $this->formTemplateDataProviderRegistry->expects($this->once())->method('has')->willReturn(false);
        $this->assembler->assemble(self::$transitionConfiguration, $transition);
    }

    public function testAssemble()
    {
        $transition = $this->createMock(Transition::class);
        $this->assertTransitionMethodsCalled($transition, true);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(true);
        $this->formHandlerRegistry->expects($this->once())->method('has')->willReturn(true);
        $this->formTemplateDataProviderRegistry->expects($this->once())->method('has')->willReturn(true);
        $this->assembler->assemble(self::$transitionConfiguration, $transition);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $transition
     * @param bool $called
     */
    protected function assertTransitionMethodsCalled(\PHPUnit_Framework_MockObject_MockObject $transition, $called)
    {
        $expects = $called ? $this->any() : $this->never();
        $transition->expects($expects)->method('setPageFormHandler')->willReturn($transition);
        $transition->expects($expects)->method('setPageFormDataAttribute')->willReturn($transition);
        $transition->expects($expects)->method('setPageFormTemplate')->willReturn($transition);
        $transition->expects($expects)->method('setPageFormDataProvider')->willReturn($transition);
        $transition->expects($expects)->method('setHasPageFormConfiguration')->willReturn($transition);
    }
}
