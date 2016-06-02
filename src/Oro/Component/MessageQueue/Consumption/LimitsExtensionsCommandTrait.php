<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

trait LimitsExtensionsCommandTrait
{
    /**
     * {@inheritdoc}
     */
    protected function configureLimitsExtensions()
    {
        $this
            ->addOption('message-limit', InputOption::VALUE_REQUIRED, 'Consume n messages and exit')
            ->addOption('time-limit', InputOption::VALUE_REQUIRED, 'Consume messages during this time')
            ->addOption('memory-limit', InputOption::VALUE_REQUIRED, 'Consume messages until process reaches'.
                ' this memory limit in MB');
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return ExtensionInterface[]
     */
    protected function getLimitsExtensions(InputInterface $input, OutputInterface $output)
    {
        $extensions = [new LoggerExtension(new ConsoleLogger($output))];

        if ($messageLimit = (int) $input->getOption('message-limit')) {
            $extensions[] = new LimitConsumedMessagesExtension($messageLimit);
        }

        if ($timeLimit = $input->getOption('time-limit')) {
            try {
                $timeLimit = new \DateTime($timeLimit);
            } catch (\Exception $e) {
                $output->writeln('<error>Invalid time limit</error>');

                throw $e;
            }

            $extensions[] = new LimitConsumptionTimeExtension($timeLimit);
        }

        if ($memoryLimit = (int) $input->getOption('memory-limit')) {
            $extensions[] = new LimitConsumerMemoryExtension($memoryLimit);
        }

        return $extensions;
    }
}
