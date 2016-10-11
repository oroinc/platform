<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Extension\TranslationContextResolver;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplateParametersResolver;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class TranslationContextResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var KeyTemplateParametersResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $resolver;

    /** @var TranslationContextResolver */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->resolver = $this->getMockBuilder(KeyTemplateParametersResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new TranslationContextResolver($this->translator, $this->resolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->translator, $this->resolver, $this->extension);
    }

    /**
     * @param string $inputKey
     * @param string $resolvedId
     * @param array $resolvedParameters
     *
     * @dataProvider resolveProvider
     */
    public function testResolve($inputKey, $resolvedId, $resolvedParameters)
    {
        $this->resolver->expects($this->once())
            ->method('resolveTemplateParameters')
            ->with($resolvedParameters)
            ->willReturn(['argument1' => 'value1']);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($resolvedId, ['argument1' => 'value1'])
            ->willReturn('translatedString');

        $this->assertEquals('translatedString', $this->extension->resolve($inputKey));
    }

    /**
     * @return array
     */
    public function resolveProvider()
    {
        $keyPrefix = WorkflowTemplate::KEY_PREFIX;
        $templatePrefix = str_replace('{{ template }}', '', TranslationContextResolver::TRANSLATION_TEMPLATE);

        return [
            'workflow_label' => [
                'input' => $keyPrefix . '.workflow1.label',
                'resolvedId' => $templatePrefix . 'workflow_label',
                'resolvedParams' => ['workflow_name' => 'workflow1'],
            ],
            'transition_label' => [
                'input' => $keyPrefix . '.workflow1.transition.transition1.label',
                'resolvedId' => $templatePrefix . 'transition_label',
                'resolvedParams' => ['workflow_name' => 'workflow1', 'transition_name' => 'transition1'],
            ],
            'transition_warning_message' => [
                'input' => $keyPrefix . '.workflow1.transition.transition1.warning_message',
                'resolvedId' => $templatePrefix . 'transition_warning_message',
                'resolvedParams' => ['workflow_name' => 'workflow1', 'transition_name' => 'transition1'],
            ],
            'step_label' => [
                'input' => $keyPrefix . '.workflow1.step.step1.label',
                'resolvedId' => $templatePrefix . 'step_label',
                'resolvedParams' => ['workflow_name' => 'workflow1', 'step_name' => 'step1'],
            ],
            'attribute_label' => [
                'input' => $keyPrefix . '.workflow1.attribute.attribute1.label',
                'resolvedId' => $templatePrefix . 'attribute_label',
                'resolvedParams' => ['workflow_name' => 'workflow1', 'attribute_name' => 'attribute1'],
            ],
        ];
    }

    /**
     * @param string $input
     *
     * @dataProvider resolveUnresolvedKeysProvider
     */
    public function testResolveUnresolvedKeys($input)
    {
        $this->translator->expects($this->never())->method('trans');

        $this->assertNull($this->extension->resolve($input));
    }

    /**
     * @return array
     */
    public function resolveUnresolvedKeysProvider()
    {
        return [
            'not applicable key' => [
                'input' => 'not_applicable_key',
            ],
            'unknown root key' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.unknown_key',
            ],
            'unknown workflow key' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.unknown_key',
            ],
            'unknown transition key 1' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.transition',
            ],
            'unknown transition key 2' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.transition.transition1',
            ],
            'unknown transition key 3' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.transition.transition1.unknown_key',
            ],
            'unknown step key' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.step',
            ],
            'unknown step key 2' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.step.step1',
            ],
            'unknown step key 3' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.step.step1.unknown_key',
            ],
            'unknown attribute key' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.attribute',
            ],
            'unknown attribute key 2' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.attribute.attribute1',
            ],
            'unknown attribute key 3' => [
                'input' => WorkflowTemplate::KEY_PREFIX . '.workflow1.attribute.attribute1.unknown_key',
            ],
        ];
    }
}
