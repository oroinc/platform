<?php

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to update associations to emails
 */
class UpdateAssociationsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:email:update-associations';

    /** @var MessageProducerInterface */
    private $producer;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        parent::__construct();

        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Update associations to emails');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->producer->send(Topics::UPDATE_ASSOCIATIONS_TO_EMAILS, []);

        $output->writeln('<info>Update of associations has been scheduled.</info>');
    }
}
