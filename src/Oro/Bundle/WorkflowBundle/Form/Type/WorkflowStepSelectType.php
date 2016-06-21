<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowStepSelectType extends AbstractType
{
    /**
     * @var WorkflowManager
     */
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(
            [
                'workflow_entity_class',
                'workflow_name'
            ]
        );

        $resolver->setDefaults(
            [
                'class' => 'OroWorkflowBundle:WorkflowStep',
                'property' => 'label'
            ]
        );

        $workflowManager = $this->workflowManager;

        $resolver->setNormalizers(
            [
                'query_builder' => function (Options $options, $qb) use ($workflowManager) {
                    if (!$qb) {
                        if (isset($options['workflow_name'])) {
                            $workflowName = $options['workflow_name'];
                            $workflow = $workflowManager->getWorkflow($workflowName);
                        } elseif (isset($options['workflow_entity_class'])) {
                            //todo fix in scope of BAP-10801
                            $workflow = $workflowManager
                                ->getApplicableWorkflowByEntityClass($options['workflow_entity_class']);
                        } else {
                            throw new \Exception('Either "workflow_name" or "workflow_entity_class" must be set');
                        }

                        $definition = $workflow ? $workflow->getDefinition() : null;
                        /** @var EntityManager $em */
                        $em = $options['em'];
                        $qb = $em->getRepository($options['class'])->createQueryBuilder('ws')
                            ->where('ws.definition = :workflowDefinition')
                            ->setParameter('workflowDefinition', $definition)
                            ->orderBy('ws.stepOrder', 'ASC')
                            ->orderBy('ws.label', 'ASC');
                    }

                    return $qb;
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_workflow_step_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}
