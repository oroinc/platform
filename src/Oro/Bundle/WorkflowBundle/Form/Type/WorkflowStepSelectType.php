<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowStepSelectType extends AbstractType
{
    const NAME = 'oro_workflow_step_select';

    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param TranslatorInterface $translator
     */
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
        $resolver->setDefaults(
            [
                'class' => 'OroWorkflowBundle:WorkflowStep',
                'property' => 'label'
            ]
        );

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
                    $this->translator->trans(
                        $step->getDefinition()->getLabel(),
                        [],
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN
                    ),
                    $this->translator->trans($choiceView->label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
                );
            } else {
                $choiceView->label = $this->translator->trans(
                    $choiceView->label,
                    [],
                    WorkflowTranslationHelper::TRANSLATION_DOMAIN
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * @param EntityManager $em
     * @param string $className
     * @param array $definitions
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder(EntityManager $em, $className, array $definitions)
    {
        $qb = $em->getRepository($className)->createQueryBuilder('ws');

        return $qb->where($qb->expr()->in('ws.definition', ':workflowDefinitions'))
            ->setParameter('workflowDefinitions', $definitions)
            ->orderBy('ws.definition', 'ASC')
            ->orderBy('ws.stepOrder', 'ASC')
            ->orderBy('ws.label', 'ASC');
    }

    /**
     * @param array|Options $options
     *
     * @return array
     */
    protected function getWorkflows($options)
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
}
