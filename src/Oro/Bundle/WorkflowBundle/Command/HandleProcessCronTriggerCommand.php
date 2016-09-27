<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionCronTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class HandleProcessCronTriggerCommand extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:handle-transition-cron-trigger';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Handle workflow transition cron trigger with specified identifier')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED,
                'Identifier of the transition cron trigger'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $triggerId = $input->getOption('id');
        if (!filter_var($triggerId, FILTER_VALIDATE_INT)) {
            $output->writeln('<error>No workflow transition cron trigger identifier defined</error>');
            return;
        }

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->getTransitionCronTriggerRepository()->find($triggerId);
        if (!$trigger) {
            $output->writeln('<error>Transition cron trigger not found</error>');
            return;
        }

        $workflowItems = $this->getTransitionCronTriggerHelper()->fetchWorkflowItemsForTrigger($trigger);

        $data = array_map(
            function (WorkflowItem $workflowItem) use ($trigger) {
                return [
                    'workflowItem' => $workflowItem,
                    'transition' => $trigger->getTransitionName()
                ];
            },
            $workflowItems
        );

        $manager = $this->getWorkflowManager();

        try {
            $start = microtime(true);

            $manager->massTransit($data);

            $output->writeln(
                sprintf(
                    '<info>[%s] Transition cron trigger #%d of workflow "%s" successfully finished in %f s</info>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $trigger->getWorkflowDefinition()->getName(),
                    microtime(true) - $start
                )
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>[%s] Transition cron trigger #%s of workflow "%s" failed: %s</error>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $trigger->getWorkflowDefinition()->getName(),
                    $e->getMessage()
                )
            );

            throw $e;
        }
    }

    /**
     * @return ObjectRepository
     */
    protected function getTransitionCronTriggerRepository()
    {
        $className = $this->getContainer()->getParameter('oro_workflow.entity.transition_trigger_cron.class');

        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @return TransitionCronTriggerHelper
     */
    protected function getTransitionCronTriggerHelper()
    {
        return $this->getContainer()->get('oro_workflow.helper.transition_cron_trigger');
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->getContainer()->get('oro_workflow.manager');
    }
}
