<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Model\FormOptionsConfigurationAssembler;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\CustomFormType;
use Symfony\Component\Form\FormRegistryInterface;

class FormOptionsConfigurationAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var  FormOptionsConfigurationAssembler */
    protected $assembler;

    /** @var  FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formRegistry;

    /** @var  FormHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $formHandlerRegistry;

    /** @var  FormTemplateDataProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $formTemplateDataProviderRegistry;

    /** @var array */
    protected static $transitionConfiguration = [
        'form_type' => CustomFormType::class,
        'form_options' => [
            WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION => [
                'handler' => 'CustomFormHandler',
                'data_provider' => 'CustomFormDataProvider',
                'data_attribute' => 'CustomFormDataAttribute',
                'template' => 'CustomTemplate'
            ],
        ],
    ];

    protected function setUp()
    {
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);
        $this->formHandlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->formTemplateDataProviderRegistry = $this->createMock(FormTemplateDataProviderRegistry::class);

        $this->assembler = new FormOptionsConfigurationAssembler(
            $this->formRegistry,
            $this->formHandlerRegistry,
            $this->formTemplateDataProviderRegistry
        );
    }

    public function testAssembleUnregisteredFormTypeException()
    {
        $transitionConfiguration = self::$transitionConfiguration;
        $transitionConfiguration['form_type'] = 'UnknownFormType';

        $expectedExceptionMessage = sprintf(
            'Form type should be FQCN or class not found got "%s"',
            $transitionConfiguration['form_type']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->assembler->assemble($transitionConfiguration);
    }

    public function testAssembleRequiredFormTypeException()
    {
        $expectedExceptionMessage = sprintf(
            'Unable to resolve form type "%s"',
            self::$transitionConfiguration['form_type']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(false);
        $this->assembler->assemble(self::$transitionConfiguration);
    }

    public function testAssembleRequiredHandlerException()
    {
        $formOptions = self::$transitionConfiguration['form_options'];
        $expectedExceptionMessage = sprintf(
            'Unable to resolve form handler with alias "%s"',
            $formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]['handler']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(true);
        $this->formHandlerRegistry->expects($this->once())->method('has')->willReturn(false);
        $this->assembler->assemble(self::$transitionConfiguration);
    }

    public function testAssembleRequiredDataProviderException()
    {
        $formOptions = self::$transitionConfiguration['form_options'];
        $expectedExceptionMessage = sprintf(
            'Unable to resolve form data provider with alias "%s"',
            $formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]['data_provider']
        );
        $this->expectException('\Oro\Bundle\WorkflowBundle\Exception\AssemblerException');
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(true);
        $this->formHandlerRegistry->expects($this->once())->method('has')->willReturn(true);
        $this->formTemplateDataProviderRegistry->expects($this->once())->method('has')->willReturn(false);
        $this->assembler->assemble(self::$transitionConfiguration);
    }

    public function testAssemble()
    {
        $this->formRegistry->expects($this->once())->method('hasType')->willReturn(true);
        $this->formHandlerRegistry->expects($this->once())->method('has')->willReturn(true);
        $this->formTemplateDataProviderRegistry->expects($this->once())->method('has')->willReturn(true);
        $this->assembler->assemble(self::$transitionConfiguration);
    }
}
