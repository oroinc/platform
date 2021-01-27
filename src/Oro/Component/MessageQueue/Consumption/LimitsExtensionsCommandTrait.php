<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides methods to add command options limit memory, time and number of processed messages,
 * and to configure the limit extensions based on user input.
 */
trait LimitsExtensionsCommandTrait
{
    protected function configureLimitsExtensions()
    {
        /** @var Command $this */
        $this
            ->addOption('message-limit', null, InputOption::VALUE_REQUIRED, 'Consume n messages and exit')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Exit after this time')
            ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Exit if this memory limit (MB) is reached')
            ->setHelp(
                // @codingStandardsIgnoreStart
            $this->getHelp() . <<<'HELP'

The <info>--message-limit</info> option can be used to limit the maximum number of messages
to consume before exiting:

  <info>php %command.full_name% --message-limit=<number></info> <fg=green;options=underscore>other options and arguments</>

The <info>--time-limit</info> option can be used to restrict the run time. Accepts any date/time
value recognized by PHP (see <comment>https://php.net/manual/datetime.formats.php</comment>):

  <info>php %command.full_name% --time-limit=<date-time-string></info> <fg=green;options=underscore>other options and arguments</>

The <info>--memory-limit</info> option defines the maximum used memory threshold (megabytes):

  <info>php %command.full_name% --memory-limit=<number></info></info> <fg=green;options=underscore>other options and arguments</>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--message-limit=<number> [other options and arguments]')
            ->addUsage('--time-limit=<date-time-string> [other options and arguments]')
            ->addUsage('--memory-limit=<number-of-megabytes> [other options and arguments]')
        ;
    }

    /**
     * @return ExtensionInterface[]
     *
     * @throws \Exception
     */
    protected function getLimitsExtensions(InputInterface $input, OutputInterface $output): array
    {
        $extensions = [];

        $messageLimit = (int) $input->getOption('message-limit');
        if ($messageLimit) {
            $extensions[] = new LimitConsumedMessagesExtension($messageLimit);
        }

        $timeLimit = $input->getOption('time-limit');
        if ($timeLimit) {
            try {
                $timeLimit = new \DateTime($timeLimit);
            } catch (\Exception $e) {
                $output->writeln('<error>Invalid time limit</error>');

                throw $e;
            }

            $extensions[] = new LimitConsumptionTimeExtension($timeLimit);
        }

        $memoryLimit = (int) $input->getOption('memory-limit');
        if ($memoryLimit) {
            $extensions[] = new LimitConsumerMemoryExtension($memoryLimit);
        }

        return $extensions;
    }
}
