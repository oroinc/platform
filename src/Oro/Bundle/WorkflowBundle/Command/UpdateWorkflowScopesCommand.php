<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowScopeConfigurationException;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

class UpdateWorkflowScopesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:workflow:scope:update')
            ->setDescription('Update WorkflowScope entities for WorkflowDefinition entities stored in database.')
            ->addOption(
                'disable-on-error',
                null,
                InputOption::VALUE_NONE,
                'Disable WorkflowDefinition on error without error triggering'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $disableOnError = (bool)$input->getOption('disable-on-error');

        $manager = $this->getWorkflowScopeManager();
        $definitions = $this->getWorkflowDefinitions();

        foreach ($definitions as $definition) {
            $output->writeln(sprintf('Updating workflow scopes for workflow "%s"...', $definition->getName()));

            try {
                $manager->updateScopes($definition);
            } catch (WorkflowScopeConfigurationException $e) {
                if ($disableOnError) {
                    $this->disableWorkflow($definition->getName());

                    $output->writeln(
                        sprintf('Workflow "%s" disabled. Reason: %s.', $definition->getName(), $e->getMessage())
                    );
                } else {
                    throw $e;
                }
            }
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

    /**
     * @param $workflowName
     */
    protected function disableWorkflow($workflowName)
    {
        $this->getContainer()->get('oro_workflow.manager')->deactivateWorkflow($workflowName);
    }
}
