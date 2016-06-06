<?php
namespace Oro\Component\MessageQueue\Client\Meta;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TopicsCommand extends Command
{
    /**
     * @var TopicMetaRegistry
     */
    private $topicRegistry;

    /**
     * @param TopicMetaRegistry $topicRegistry
     */
    public function __construct(TopicMetaRegistry $topicRegistry)
    {
        parent::__construct('oro:message-queue:topics');

        $this->topicRegistry = $topicRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A command shows all available topics and some information about them.')
        ;
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
        foreach ($this->topicRegistry->getTopicsMeta() as $topic) {
            if (false == $firstRow) {
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
