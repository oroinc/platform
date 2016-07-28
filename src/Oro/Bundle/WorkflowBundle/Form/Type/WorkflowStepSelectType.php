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

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowStepSelectType extends AbstractType
{
    const NAME = 'oro_workflow_step_select';

    /** @var WorkflowManager */
    protected $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
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
        if (count($this->getWorkflows($options)) > 1) {
            /** @var ChoiceView $choiceView */
            foreach ($view->vars['choices'] as $choiceView) {
                /** @var WorkflowStep $step */
                $step = $choiceView->data;

                $choiceView->label = sprintf('%s: %s', $step->getDefinition()->getLabel(), $choiceView->label);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
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
     * @return array
     */
    protected function getWorkflows($options)
    {
        if (isset($options['workflow_name'])) {
            $workflowName = $options['workflow_name'];
            $workflows = [$this->workflowManager->getWorkflow($workflowName)];
        } elseif (isset($options['workflow_entity_class'])) {
            $workflows = array_values(
                $this->workflowManager->getApplicableWorkflows($options['workflow_entity_class'])
            );
        } else {
            throw new \InvalidArgumentException('Either "workflow_name" or "workflow_entity_class" must be set');
        }

        return $workflows;
    }
}
