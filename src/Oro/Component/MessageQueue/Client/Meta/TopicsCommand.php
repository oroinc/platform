<?php

namespace Oro\Component\MessageQueue\Client\Meta;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows all available topics and some information about them.
 */
class TopicsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:topics';

    /** @var TopicMetaRegistry */
    private $topicMetaRegistry;

    /**
     * @param TopicMetaRegistry $topicMetaRegistry
     */
    public function __construct(TopicMetaRegistry $topicMetaRegistry)
    {
        parent::__construct();

        $this->topicMetaRegistry = $topicMetaRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A command shows all available topics and some information about them.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Topic', 'Description', 'Subscribers']);

        $count = 0;
        $firstRow = true;
        foreach ($this->getTopics() as $topic) {
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

    /**
     * @return TopicMeta[]
     */
    private function getTopics()
    {
        return $this->topicMetaRegistry->getTopicsMeta();
    }
}
