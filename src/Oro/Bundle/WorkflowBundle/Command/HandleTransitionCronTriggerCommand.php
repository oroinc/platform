<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerProcessor;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionCronTriggerHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HandleTransitionCronTriggerCommand extends ContainerAwareCommand
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
            return 1;
        }

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->getTransitionCronTriggerRepository()->find($triggerId);
        if (!$trigger) {
            $output->writeln('<error>Transition cron trigger not found</error>');
            return 1;
        }

        try {
            $start = microtime(true);
            $message = TransitionTriggerMessage::create($trigger);

            if ($trigger->isQueued()) {
                $this->getProducer()->send(TransitionTriggerProcessor::CRON_TOPIC_NAME, $message->toArray());
            } else {
                $this->getTransitionCronTriggerHandler()->process($trigger, $message);
            }

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
     * @return TransitionCronTriggerHandler
     */
    protected function getTransitionCronTriggerHandler()
    {
        return $this->getContainer()->get('oro_workflow.handler.transition_cron_trigger');
    }


    /**
     * @return MessageProducerInterface
     */
    protected function getProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
