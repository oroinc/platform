<?php

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAssociationsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:update-associations')
            ->setDescription('Update associations to emails');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->getProducer()
            ->send(Topics::UPDATE_ASSOCIATIONS_TO_EMAILS, []);

        $output->writeln('<info>Update of associations has been scheduled.</info>');
    }

    /**
     * @return MessageProducerInterface
     */
    protected function getProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
