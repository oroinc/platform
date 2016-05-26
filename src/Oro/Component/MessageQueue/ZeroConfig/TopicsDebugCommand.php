<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TopicsDebugCommand extends Command
{
    /**
     * @var TopicRegistry
     */
    private $topicRegistry;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param TopicRegistry $topicRegistry
     * @param Router $router
     */
    public function __construct(TopicRegistry $topicRegistry, Router $router)
    {
        parent::__construct('oro:message-queue:zeroconfig:debug-topics');

        $this->topicRegistry = $topicRegistry;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A command shows all available topics and some information about them')
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
        foreach ($this->topicRegistry->getTopics() as $topic) {
            $subscribers = $this->router->getTopicSubscribers($topic->getName());

            $subscribersText = array_reduce($subscribers, function ($carry, $item) {
                $carry = (string) $carry;
                $carry .= $item[0];
                $carry .= PHP_EOL;

                return $carry;
            });

            $table->addRow([$topic->getName(), $topic->getDescription(), $subscribersText]);

            $count++;
        }

        if ($count > 0) {
            $table->render();
        }

        $output->writeln('');
        $output->writeln(sprintf('Found %s topics', $count));
    }
}
