<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Client;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use Oro\Component\MessageQueue\Consumption\QueueOptionValueParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Processes messages from the specified client-level queue(s), e.g. "default".
 */
class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    /** @var string */
    protected static $defaultName = 'oro:message-queue:consume';

    protected QueueConsumer $queueConsumer;

    protected DestinationMetaRegistry $destinationMetaRegistry;

    protected JobManager $jobManager;

    protected ?QueueIteratorFactoryRegistry $queueIteratorFactoryRegistry = null;

    protected ?QueueOptionValueParser $queueOptionValueParser = null;

    public function __construct(
        QueueConsumer $queueConsumer,
        DestinationMetaRegistry $destinationMetaRegistry,
    ) {
        $this->queueConsumer = $queueConsumer;
        $this->destinationMetaRegistry = $destinationMetaRegistry;

        parent::__construct();
    }

    public function setQueueIteratorFactoryRegistry(QueueIteratorFactoryRegistry $queueIteratorFactoryRegistry): void
    {
        $this->queueIteratorFactoryRegistry = $queueIteratorFactoryRegistry;
    }

    public function setQueueOptionValueParser(QueueOptionValueParser $queueOptionValueParser): void
    {
        $this->queueOptionValueParser = $queueOptionValueParser;
    }

    public function setJobManager(JobManager $jobManager): void
    {
        $this->jobManager = $jobManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queues to consume messages from')
            ->addOption(
                'queue',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Queue specification for long notation; repeatable. Each value is either a plain'
                . ' client-level queue name (e.g. --queue=default) or a key=value string'
                . ' (e.g. --queue="name=default,weight=3").'
                . ' Cannot be combined with the "queue" positional argument.',
                []
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_REQUIRED,
                'Consumption mode.',
                DefaultQueueIterator::NAME
            )
            ->setHelp(
                <<<HELP
The <info>%command.name%</info> command processes messages from the specified client-level queue(s).

<comment>Short notation</comment> - queue names as a comma-separated argument:
  <info>php %command.full_name%</info>
  <info>php %command.full_name% default</info>
  <info>php %command.full_name% default,alternate</info>

<comment>Long notation</comment> - each queue specified via a repeatable --queue option:
  <info>php %command.full_name% --queue=default</info>
  <info>php %command.full_name% --queue=default --queue=alternate</info>
  <info>php %command.full_name% --queue="name=default,weight=3"</info>
  <info>php %command.full_name% --queue="name=default,weight=3" --queue=alternate --mode=default</info>

In long notation each --queue value is either a plain client-level queue name or a comma-separated
key=value string. Recognized keys: <info>name</info> (required). All other keys are forwarded
as extra settings.

The short and long notations are mutually exclusive.

<comment>Consumption modes</comment> (<info>--mode</info>) - visit order over bound queues in <info>binding order</info>
(<info>Q1</info> ... first bound queue, <info>Q2</info> ... second, ...):

  <info>(1)</info> means one poll/consume from that queue at that slot.
  <info>(*)</info> means "stay until that queue poll is idle".
  <info>(w)</info> is the configured weight (weighted-round-robin).

  <info>default</info> - one slot per queue, then repeats:
    2 queues: Q1(1), Q2(1), Q1(1), Q2(1), ...
    3 queues: Q1(1), Q2(1), Q3(1), Q1(1), Q2(1), Q3(1), ...

  <info>sequential-exhaustive</info> - drain each queue until idle once:
    2 queues: Q1(*), Q2(*)
    3 queues: Q1(*), Q2(*), Q3(*)

  <info>strict-priority-interleaving</info> - Q1 until idle, one poll of each lower queue between returns:
    2 queues: Q1(*), Q2(1)
    3 queues: Q1(*), Q2(1), Q1(*), Q3(1)

  <info>hierarchical-strict-priority-interleaving</info> - nested strict-priority; drain higher queues as one block:
    2 queues: Q1(*), Q2(1)
    3 queues: ( Q1(*), Q2(1) )(*), Q3(1)
    Further queue counts nest the same pattern.

  <info>weighted-round-robin</info> - consume up to <info>w</info> consecutive messages before switching:
    2 queues: Q1(w1), Q2(w2), Q1(w1), Q2(w2), ...
    3 queues: Q1(w1), Q2(w2), Q3(w3), Q1(w1), Q2(w2), Q3(w3), ...
    Idle poll skips remainder of weight for that slot.

When no queue is specified, all registered client-level queues are consumed.

See the official Oro documentation for the MessageQueue bundle for more information.

HELP
            );
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueArgument = $input->getArgument('queue');
        $queueOption = $input->getOption('queue');

        $hasQueueArg = ($queueArgument !== null && $queueArgument !== '');
        $hasQueueOption = ($queueOption !== []);

        $styledOutput = new SymfonyStyle($input, $output);

        if (!$this->validateNotationInputs($hasQueueArg, $hasQueueOption, $styledOutput)) {
            return self::FAILURE;
        }

        $consumptionMode = (string)$input->getOption('mode');
        if (!$this->validateConsumptionMode($consumptionMode, $styledOutput)) {
            return self::FAILURE;
        }
        $this->queueConsumer->setConsumptionMode($consumptionMode);

        $extensions = $this->getLimitsExtensions($input, $styledOutput);
        array_unshift($extensions, $this->getLoggerExtension($input, $styledOutput));

        if (!$this->bindQueues($hasQueueArg, $input, $styledOutput)) {
            return self::FAILURE;
        }

        $this->consume($this->queueConsumer, $this->getConsumerExtension($extensions));

        return self::SUCCESS;
    }

    /**
     * Validates that the input uses exactly one queue-binding notation and that incompatible options are not combined.
     *
     * @return bool Returns true when inputs are valid; writes an error message and returns false when they are not.
     */
    private function validateNotationInputs(
        bool $hasQueueArg,
        bool $hasQueueOption,
        SymfonyStyle $styledOutput,
    ): bool {
        if ($hasQueueArg && $hasQueueOption) {
            $styledOutput->error(
                'Cannot use both the "queue" positional argument and the "--queue" option at the same time. '
                . 'Use one notation or the other.'
            );

            return false;
        }

        return true;
    }

    /**
     * Validates that specified consumption mode is supported.
     *
     * @return bool Returns true if the mode is supported; writes an error message and returns false if not.
     */
    private function validateConsumptionMode(string $consumptionMode, SymfonyStyle $styledOutput): bool
    {
        $consumptionModes = $this->queueIteratorFactoryRegistry?->getConsumptionModes() ?? [];

        if ($consumptionMode === DefaultQueueIterator::NAME) {
            return true;
        }

        if (!$consumptionModes) {
            $styledOutput->error(
                'No non-default consumption modes are registered in the system. Please check your configuration.'
            );
            return false;
        }

        if (!\in_array($consumptionMode, $consumptionModes, true)) {
            $supportedModes = implode(', ', $consumptionModes);
            $styledOutput->error(
                sprintf(
                    'Unknown consumption mode "%s". Supported modes: %s',
                    $consumptionMode,
                    $supportedModes
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Binds queues to the consumer based on the active notation (short or long).
     * Returns true on success; writes an error message and returns false on failure.
     */
    private function bindQueues(
        bool $hasQueueArg,
        InputInterface $input,
        SymfonyStyle $styledOutput,
    ): bool {
        if ($hasQueueArg) {
            // Short notation.
            $queueNames = $this->getQueueNames($input, $styledOutput);
            if (!$queueNames) {
                return false;
            }

            foreach ($queueNames as $queueName) {
                $transportQueueName = $this->destinationMetaRegistry
                    ->getDestinationMeta($queueName)
                    ->getTransportQueueName();
                $this->queueConsumer->bindQueue($transportQueueName);
            }
        } elseif ($this->queueOptionValueParser !== null && $input->getOption('queue') !== []) {
            // Long notation.
            $queues = $this->resolveQueuesFromOption($input, $styledOutput);
            if (!$queues) {
                return false;
            }

            foreach ($queues as $queueName => $queueSettings) {
                $transportQueueName = $this->destinationMetaRegistry
                    ->getDestinationMeta($queueName)
                    ->getTransportQueueName();

                $this->queueConsumer->bindQueue($transportQueueName, $queueSettings);
            }
        } else {
            // Default: consume all registered destinations.
            foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $destinationMeta) {
                $this->queueConsumer->bindQueue($destinationMeta->getTransportQueueName());
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    private function getQueueNames(InputInterface $input, SymfonyStyle $styledOutput): array
    {
        $queueArgument = (string)$input->getArgument('queue');
        $queueNames = array_values(array_filter(array_map('trim', explode(',', $queueArgument))));
        if (!$queueNames) {
            $styledOutput->error(
                'The "queue" argument must contain at least one client-level queue name when used in short notation.'
            );
        }

        return $queueNames;
    }

    /**
     * @return array<string, array>
     */
    private function resolveQueuesFromOption(InputInterface $input, SymfonyStyle $styledOutput): array
    {
        $rawValues = $input->getOption('queue');
        $resolved = [];

        foreach ($rawValues as $rawValue) {
            $result = $this->queueOptionValueParser?->parse($rawValue) ?? ['name' => '', 'queueSettings' => []];
            ['name' => $queueName, 'queueSettings' => $queueSettings] = $result;
            if ($queueName === '') {
                $styledOutput->error(
                    sprintf(
                        'A --queue value resolved to an empty client-level queue name. Original value: "%s"',
                        $rawValue
                    )
                );

                return [];
            }

            if (array_key_exists($queueName, $resolved)) {
                $styledOutput->error(
                    sprintf(
                        'Duplicate --queue value: client-level queue "%s" was specified more than once.',
                        $queueName
                    )
                );

                return [];
            }

            $resolved[$queueName] = $queueSettings;
        }

        return $resolved;
    }

    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension): void
    {
        try {
            $consumer->consume($extension);
        } finally {
            $consumer->getConnection()->close();
        }
    }

    protected function getConsumerExtension(array $extensions): ExtensionInterface
    {
        return new ChainExtension($extensions);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output): ExtensionInterface
    {
        return new LoggerExtension(new ConsoleLogger($output));
    }
}
