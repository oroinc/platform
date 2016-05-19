<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class ExecuteWorkflowTransitionCommand extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:transition:execute';

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Execute transition of workflow')
            ->addOption(
                'workflow-item',
                null,
                InputOption::VALUE_REQUIRED,
                'Identifier of WorkflowItem'
            )
            ->addOption(
                'transition',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of Transition'
            );
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workflowItemId = $input->getOption('workflow-item');
        $transitionName = $input->getOption('transition');

        if (!filter_var($workflowItemId, FILTER_VALIDATE_INT)) {
            $output->writeln('<error>No Workflow Item identifier defined</error>');
            return;
        }

        if (!$transitionName) {
            $output->writeln('<error>No Transition name defined</error>');
            return;
        }

        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getRepository()->find($workflowItemId);
        if (!$workflowItem) {
            $output->writeln('<error>Workflow Item not found</error>');
            return;
        }

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');

        $output->writeln('<info>Start transition...</info>');
        try {
            $workflowManager->transit($workflowItem, $transitionName);
            $output->writeln(
                sprintf(
                    '<info>Workflow Item #%s transition #%s successfully finished</info>',
                    $workflowItemId,
                    $transitionName
                )
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>[%s] Transition #%s is failed: %s</error>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $transitionName,
                    $e->getMessage()
                )
            );

            throw $e;
        }
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        $className = $this->getContainer()->getParameter('oro_workflow.workflow_item.entity.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}
