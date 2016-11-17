<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

class UpdateWorkflowScopesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:workflow:scope:update')
            ->setDescription('Update WorkflowScope entities for WorkflowDefinition entities stored in database.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getWorkflowScopeManager();
        $definitions = $this->getWorkflowDefinitions();

        foreach ($definitions as $definition) {
            $output->writeln(sprintf('Updating workflow scopes for workflow "%s"...', $definition->getName()));

            $manager->updateScopes($definition);
        }
    }

    /**
     * @return array|WorkflowDefinition[]
     */
    protected function getWorkflowDefinitions()
    {
        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class);

        return $repository->findAll();
    }

    /**
     * @return WorkflowScopeManager
     */
    protected function getWorkflowScopeManager()
    {
        return $this->getContainer()->get('oro_workflow.manager.workflow_scope');
    }
}
