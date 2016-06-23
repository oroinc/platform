<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowDefinitionSelectType extends AbstractType
{
    const NAME = 'oro_workflow_definition_select';

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
                'class' => 'OroWorkflowBundle:WorkflowDefinition',
                'property' => 'label'
            ]
        );

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $choices) {
                if (!empty($choices)) {
                    return $choices;
                }
                
                if (isset($options['workflow_name'])) {
                    $workflowName = $options['workflow_name'];
                    $workflows = [$this->workflowManager->getWorkflow($workflowName)];
                } elseif (isset($options['workflow_entity_class'])) {
                    $workflows = $this->workflowManager->getApplicableWorkflowsByEntityClass(
                        $options['workflow_entity_class']
                    );
                } else {
                    throw new \InvalidArgumentException(
                        'Either "workflow_name" or "workflow_entity_class" must be set'
                    );
                }

                $definitions = [];
                
                /** @var Workflow[] $workflows */
                foreach ($workflows as $workflow) {
                    $definition = $workflow->getDefinition();
                    
                    $definitions[$definition->getName()] = $definition;
                }

                return $definitions;
            }
        );
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
}
