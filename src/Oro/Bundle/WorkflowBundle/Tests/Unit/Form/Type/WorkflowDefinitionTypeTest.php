<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionType;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionChoicesGroupProvider;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type\Stub\ApplicableEntitiesTypeStub;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type\Stub\OroIconTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowDefinitionTypeTest extends FormIntegrationTestCase
{
    /** @var WorkflowDefinitionChoicesGroupProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $choicesProvider;

    /** @var WorkflowDefinitionType */
    private $formType;

    protected function setUp(): void
    {
        $this->choicesProvider = $this->createMock(WorkflowDefinitionChoicesGroupProvider::class);
        $this->formType = new WorkflowDefinitionType($this->choicesProvider);
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $fields, array $submittedData, array $expectedData)
    {
        $this->choicesProvider->expects($this->any())
            ->method('getActiveGroupsChoices')
            ->willReturn([]);
        $this->choicesProvider->expects($this->any())
            ->method('getRecordGroupsChoices')
            ->willReturn([]);

        $form = $this->factory->create(WorkflowDefinitionType::class);

        foreach ($fields as $field => $options) {
            $this->assertTrue($form->has($field));
            $config = $form->get($field)->getConfig();

            foreach ($options as $option => $value) {
                $this->assertEquals($value, $config->getOption($option));
            }
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        foreach ($expectedData as $field => $data) {
            $this->assertTrue($form->has($field));
            $this->assertEquals($data, $form->get($field)->getData());
        }
    }

    public function submitDataProvider(): array
    {
        return [
            [
                'fields' => [
                    'label' => ['required' => true],
                    'related_entity' => ['required' => true],
                    'steps_display_ordered' => ['required' => false],
                    'transition_prototype_icon' => ['required' => false],
                    'exclusive_active_groups' => ['required' => false],
                    'exclusive_record_groups' => ['required' => false],
                ],
                'submittedData' => [
                    'label' => 'label',
                    'related_entity' => 'stdClass',
                    'steps_display_ordered' => true,
                    'transition_prototype_icon' => null,
                    'exclusive_active_groups' => [],
                    'exclusive_record_groups' => [],
                ],
                'expectedData' => [
                    'label' => 'label',
                    'related_entity' => 'stdClass',
                    'steps_display_ordered' => true,
                    'transition_prototype_icon' => null,
                    'exclusive_active_groups' => [],
                    'exclusive_record_groups' => [],
                ]
            ]
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => WorkflowDefinition::class]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(WorkflowDefinitionType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $choiceType = $this->createMock(OroChoiceType::class);
        $choiceType->expects($this->any())
            ->method('getParent')
            ->willReturn(ChoiceType::class);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $this->formType,
                        $choiceType,
                        OroIconType::class => new OroIconTypeStub(),
                        ApplicableEntitiesType::class => new ApplicableEntitiesTypeStub()
                    ],
                    [
                        FormType::class => [new TooltipFormExtensionStub($this)]
                    ]
                ),
                $this->getValidatorExtension(false)
            ]
        );
    }
}
