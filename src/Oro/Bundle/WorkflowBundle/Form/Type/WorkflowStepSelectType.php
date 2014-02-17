<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            array(
                'workflow_entity_class',
                'workflow_name'
            )
        );

        $resolver->setDefaults(
            array(
                'class' => 'OroWorkflowBundle:WorkflowStep',
                'property' => 'label'
            )
        );

        $workflowManager = $this->workflowManager;

        $resolver->setNormalizers(
            array(
                'query_builder' => function (Options $options, $qb) use ($workflowManager) {
                    if (!$qb) {
                        if (isset($options['workflow_name'])) {
                            $workflowName = $options['workflow_name'];
                            $workflow = $workflowManager->getWorkflow($workflowName);
                        } elseif (isset($options['workflow_entity_class'])) {
                            $workflow = $workflowManager
                                ->getApplicableWorkflowByEntityClass($options['workflow_entity_class']);
                        } else {
                            throw new \Exception('Either "workflow_name" or "workflow_entity_class" must be set');
                        }

                        $definition = $workflow ? $workflow->getDefinition() : null;
                        $qb = function (EntityRepository $er) use ($definition) {
                            return $er->createQueryBuilder('ws')
                                ->where('ws.definition = :workflowDefinition')
                                ->setParameter('workflowDefinition', $definition)
                                ->orderBy('ws.stepOrder', 'ASC')
                                ->orderBy('ws.label', 'ASC');
                        };
                    }

                    return $qb;
                },
            )
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
