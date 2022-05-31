<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type provides functionality to select a WorkflowStep.
 */
class WorkflowStepSelectType extends AbstractType
{
    private WorkflowRegistry $workflowRegistry;
    private TranslatorInterface $translator;

    public function __construct(WorkflowRegistry $workflowRegistry, TranslatorInterface $translator)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['workflow_entity_class', 'workflow_name']);
        $resolver->setDefaults([
            'class' => WorkflowStep::class,
            'choice_label' => 'label'
        ]);

        $resolver->setNormalizer(
            'query_builder',
            function (Options $options, $qb) {
                if (!$qb) {
                    $qb = $this->getQueryBuilder(
                        $options['em'],
                        $options['class'],
                        array_map(
                            function (Workflow $workflow) {
                                return $workflow->getDefinition();
                            },
                            $this->getWorkflows($options)
                        )
                    );
                }

                return $qb;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $workflowsCount = count($this->getWorkflows($options));

        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            if ($workflowsCount > 1) {
                /** @var WorkflowStep $step */
                $step = $choiceView->data;
                $choiceView->label = sprintf(
                    '%s: %s',
                    $this->getTranslation($step->getDefinition()->getLabel()),
                    $this->getTranslation($choiceView->label)
                );
            } else {
                $choiceView->label = $this->getTranslation($choiceView->label);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_workflow_step_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }

    private function getQueryBuilder(EntityManagerInterface $em, string $className, array $definitions): QueryBuilder
    {
        return $em->getRepository($className)
            ->createQueryBuilder('ws')
            ->where('ws.definition IN (:workflowDefinitions)')
            ->setParameter('workflowDefinitions', $definitions)
            ->orderBy('ws.definition', 'ASC')
            ->orderBy('ws.stepOrder', 'ASC')
            ->orderBy('ws.label', 'ASC');
    }

    private function getWorkflows(Options|array $options): array
    {
        if (isset($options['workflow_name'])) {
            $workflowName = $options['workflow_name'];
            $workflows = [$this->workflowRegistry->getWorkflow($workflowName)];
        } elseif (isset($options['workflow_entity_class'])) {
            $workflows = $this->workflowRegistry->getActiveWorkflowsByEntityClass($options['workflow_entity_class'])
                ->getValues();
        } else {
            throw new \InvalidArgumentException('Either "workflow_name" or "workflow_entity_class" must be set');
        }

        return $workflows;
    }

    private function getTranslation(?string $value): string
    {
        return null !== $value
            ? $this->translator->trans($value, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            : '';
    }
}
