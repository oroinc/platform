<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Processes messages from the specified transport-level queue(s), e.g. "oro.default".
 */
#[AsCommand(
    name: 'oro:message-queue:transport:consume',
    description: 'Processes messages from the specified transport-level queue(s), e.g. "oro.default".'
)]
class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    protected QueueConsumer $queueConsumer;

    protected JobManager $jobManager;

    protected ?QueueIteratorFactoryRegistry $queueIteratorFactoryRegistry = null;

    protected ?QueueOptionValueParser $queueOptionValueParser = null;

    public function __construct(QueueConsumer $queueConsumer)
    {
        parent::__construct();

        $this->queueConsumer = $queueConsumer;
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
            ->addArgument(
                'queue',
                InputArgument::OPTIONAL,
                'Transport-level queue name(s) for short notation; '
                . 'separate multiple with commas (e.g. oro.default,oro.system). Cannot be combined with --queue.'
            )
            ->addArgument(
                'processor-service',
                InputArgument::OPTIONAL,
                'The message processor service ID. Leave empty to auto-detect processors based on the messages topics.'
                . ' Cannot be combined with --queue.',
            )
            ->addOption(
                'queue',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Queue specification for long notation; repeatable. Each value is either a plain '
                . 'transport-level queue name (e.g. --queue=oro.default) or a key=value string'
                . ' (e.g. --queue="name=oro.index,processor=acme.proc,weight=3").'
                . ' Cannot be combined with the "queue" positional argument.',
                []
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_REQUIRED,
                'Consumption mode.',
                DefaultQueueIterator::NAME
            );

        $this->setHelp(self::getTransportConsumeCommandHelp());
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueArgument = $input->getArgument('queue');
        $queueOption = $input->getOption('queue');
        $processorArg = (string)$input->getArgument('processor-service');

        $hasQueueArg = ($queueArgument !== null && $queueArgument !== '');
        $hasQueueOption = ($queueOption !== []);

        $styledOutput = new SymfonyStyle($input, $output);

        if (!$this->validateNotationInputs($hasQueueArg, $hasQueueOption, $processorArg, $styledOutput)) {
            return self::FAILURE;
        }

        $consumptionMode = (string)$input->getOption('mode');
        if (!$this->validateConsumptionMode($consumptionMode, $styledOutput)) {
            return self::FAILURE;
        }
        $this->queueConsumer->setConsumptionMode($consumptionMode);

        $extensions = $this->getLimitsExtensions($input, $styledOutput);
        array_unshift($extensions, $this->getLoggerExtension($input, $styledOutput));

        if (!$this->bindQueues($hasQueueArg, $processorArg, $input, $styledOutput)) {
            return self::FAILURE;
        }

        $this->consume($this->queueConsumer, $this->getConsumerExtension($extensions));

        return self::SUCCESS;
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

    /**
     * Validates that the input uses exactly one queue-binding notation and that incompatible options are not combined.
     *
     * @return bool Returns true when inputs are valid; writes an error message and returns false when they are not.
     */
    private function validateNotationInputs(
        bool $hasQueueArg,
        bool $hasQueueOption,
        string $processorArg,
        SymfonyStyle $styledOutput,
    ): bool {
        if ($hasQueueArg && $hasQueueOption) {
            $styledOutput->error(
                'Cannot use both the "queue" positional argument and the "--queue" option at the same time. '
                . 'Use one notation or the other.'
            );

            return false;
        }

        if (!$hasQueueArg && !$hasQueueOption) {
            $styledOutput->error(
                'You must provide queue names either via the "queue" argument (short notation) '
                . 'or via the "--queue" option (long notation).'
            );

            return false;
        }

        if ($hasQueueOption && $processorArg !== '') {
            $styledOutput->error(
                'The "processor-service" argument cannot be used together with the "--queue" option. '
                . 'Specify the processor inside the --queue value using the "processor" key, '
                . 'e.g. --queue="name=oro.default,processor=my_processor".'
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
        string $processorArg,
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
                $this->queueConsumer->bindQueue($queueName, [QueueConsumer::PROCESSOR => $processorArg]);
            }
        } else {
            // Long notation.
            $queues = $this->resolveQueuesFromOption($input, $styledOutput);
            if (!$queues) {
                return false;
            }

            foreach ($queues as $queueName => $queueSettings) {
                $this->queueConsumer->bindQueue($queueName, $queueSettings);
            }
        }

        return true;
    }

    /**
     * @param InputInterface $input
     * @param SymfonyStyle $styledOutput
     *
     * @return array<string>
     */
    private function getQueueNames(InputInterface $input, SymfonyStyle $styledOutput): array
    {
        $queueArgument = (string)$input->getArgument('queue');
        $queueNames = array_values(array_filter(array_map('trim', explode(',', $queueArgument))));
        if (!$queueNames) {
            $styledOutput->error(
                'The "queue" argument must contain at least one queue name when used in short notation. '
                . 'Expected format: "oro.default" or "oro.default,oro.system".'
            );
        }

        return $queueNames;
    }

    /**
     * Parses all --queue option values and returns an array of resolved queue descriptors.
     *
     * @return array<string, array{processor: string, ...}> Returns an empty array if any value resolves
     *                                                      to an empty queue name or a duplicate queue name.
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
                    sprintf('A --queue value resolved to an empty queue name. Original value: "%s"', $rawValue)
                );

                return [];
            }

            if (array_key_exists($queueName, $resolved)) {
                $styledOutput->error(
                    sprintf('Duplicate --queue value: queue "%s" was specified more than once.', $queueName)
                );

                return [];
            }

            $resolved[$queueName] = $queueSettings;
        }

        return $resolved;
    }

    private static function getTransportConsumeCommandHelp(): string
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return <<<'HELP'
The <info>%command.name%</info> command consumes messages from one or more message queues.

<comment>Short notation</comment> - queue names as a comma-separated argument, one optional processor for all queues:
  <info>php %command.full_name% oro.default</info>
  <info>php %command.full_name% oro.default,oro.system</info>
  <info>php %command.full_name% oro.default,oro.system my_processor_service</info>

<comment>Long notation</comment> - each queue specified via a repeatable --queue option:
  <info>php %command.full_name% --queue=oro.default</info>
  <info>php %command.full_name% --queue=oro.default --queue=oro.system</info>
  <info>php %command.full_name% --queue="name=oro.index,processor=oro_search.async.index_entity_processor"</info>
  <info>php %command.full_name% --queue="name=oro.index,weight=10" --queue=oro.default --mode=default</info>

In long notation each --queue value is either a plain queue name or a comma-separated key=value string.
Recognized keys: <info>name</info> (required), <info>processor</info> (optional). All other keys are forwarded
as extra settings.

The short and long notations are mutually exclusive.
The <info>processor-service</info> argument cannot be used with the long notation; embed the processor via
the <info>processor</info> key.

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

See the official Oro documentation for the MessageQueue bundle for more information.

<comment>Environment variable: ORO_MQ_CONSUMPTION_GROUPS</comment>
Defines named consumption groups as a JSON object. Each group maps queue names to queue settings (use an
empty object <info>{}</info> for default settings). When the <info>queue</info> argument matches a group name,
the command automatically expands it into the equivalent <info>--queue</info> options, replacing the positional
argument. The explicit <info>--queue</info> option always takes precedence over a group name.

Usage with a consumption group:
  <info>export ORO_MQ_CONSUMPTION_GROUPS='{"oro.default":{"oro.default":{},"oro.index":{},"oro.integration":{}}}'</info>

  <info>php %command.full_name% oro.default</info>
  (equivalent to: <info>php %command.full_name% --queue=oro.default --queue=oro.index --queue=oro.integration"</info>)

<comment>Environment variable: ORO_MQ_CONSUMPTION_MODE</comment>
Sets the default value for the <info>--mode</info> option when it is not provided explicitly on the command
line. Accepts the same values as <info>--mode</info>. The explicit <info>--mode</info> option always takes
precedence over this environment variable.

Usage with a mode:
  <info>export ORO_MQ_CONSUMPTION_MODE=hierarchical-strict-priority-interleaving</info>
  
  <info>php %command.full_name% --queue oro.index --queue oro.integration --queue oro.default</info>
  (equivalent to: <info>php %command.full_name% --queue=oro.index --queue=oro.integration --queue=oro.default --mode=hierarchical-strict-priority-interleaving"</info>)
  
Usage with both consumption group and mode:
  <info>export ORO_MQ_CONSUMPTION_GROUPS='{"oro.default":{"oro.default":{},"oro.index":{},"oro.integration":{}}}'</info>
  <info>export ORO_MQ_CONSUMPTION_MODE=hierarchical-strict-priority-interleaving</info>
  
  <info>php %command.full_name% oro.default</info>
  (equivalent to: <info>php %command.full_name% --queue=oro.default --queue=oro.index --queue=oro.integration --mode=hierarchical-strict-priority-interleaving"</info>)

HELP;

        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
