<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Client\Meta;

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

    private TopicMetaRegistry $topicMetaRegistry;

    public function __construct(TopicMetaRegistry $topicMetaRegistry)
    {
        parent::__construct();

        $this->topicMetaRegistry = $topicMetaRegistry;
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
        $table->setHeaders(['Topic', 'Description', 'Subscribers']);

        $count = 0;
        $firstRow = true;
        foreach ($this->topicMetaRegistry->getTopicsMeta() as $topic) {
            if (!$firstRow) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow([$topic->getName(), $topic->getDescription(), implode(PHP_EOL, $topic->getSubscribers())]);

            $count++;
            $firstRow = false;
        }

        $output->writeln(sprintf('Found %s topics', $count));
        $output->writeln('');
        $table->render();
    }
}
