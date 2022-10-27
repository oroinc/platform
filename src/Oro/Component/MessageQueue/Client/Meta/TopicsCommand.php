<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists available message queue topics.
 */
class TopicsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:topics';

    private TopicRegistry $topicRegistry;

    private TopicMetaRegistry $topicMetaRegistry;

    private TopicDescriptionProvider $topicDescriptionProvider;

    public function __construct(
        TopicRegistry $topicRegistry,
        TopicMetaRegistry $topicMetaRegistry,
        TopicDescriptionProvider $topicDescriptionProvider
    ) {
        parent::__construct();

        $this->topicRegistry = $topicRegistry;
        $this->topicMetaRegistry = $topicMetaRegistry;
        $this->topicDescriptionProvider = $topicDescriptionProvider;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Lists available message queue topics.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command lists available message queue topics.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Topic', 'Description', 'Default Priority', 'Subscribers']);

        $count = 0;
        $firstRow = true;
        foreach ($this->topicMetaRegistry->getTopicsMeta() as $topic) {
            if (!$firstRow) {
                $table->addRow(new TableSeparator());
            }

            $topicName = $topic->getName();
            $table->addRow(
                [
                    $topicName,
                    $this->topicDescriptionProvider->getTopicDescription($topicName),
                    $this->getDefaultPriority($topicName),
                    implode(PHP_EOL, $topic->getAllMessageProcessors()),
                ]
            );

            $count++;
            $firstRow = false;
        }

        $output->writeln(sprintf('Found %s topics', $count));
        $output->writeln('');
        $table->render();

        return self::SUCCESS;
    }

    private function getDefaultPriority(string $topicName): string
    {
        $priority = $this->topicRegistry->get($topicName)->getDefaultPriority(Config::DEFAULT_QUEUE_NAME);

        return MessagePriority::getMessagePriorityName($priority);
    }
}
