<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowExtension extends \Twig_Extension
{
    const NAME = 'oro_workflow';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->container->get('oro_workflow.registry.workflow_manager')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('has_workflows', [$this, 'hasApplicableWorkflows']),
            new \Twig_SimpleFunction('has_workflow_items', [$this, 'hasWorkflowItemsByEntity'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param object|string $entity
     *
     * @return bool
     */
    public function hasApplicableWorkflows($entity)
    {
        return $this->getWorkflowManager()->hasApplicableWorkflows($entity);
    }

    /**
     * @param object|string $entity
     *
     * @return bool
     */
    public function hasWorkflowItemsByEntity($entity)
    {
        return $this->getWorkflowManager()->hasWorkflowItemsByEntity($entity);
    }
}
