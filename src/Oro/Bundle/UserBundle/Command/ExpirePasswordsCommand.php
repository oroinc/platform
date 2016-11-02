<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\UserBundle\Async\Topics;

class ExpirePasswordsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * Run command at 00:00 every day.
     *
     * @return string
     */
    public function getDefaultDefinition()
    {
        return '0 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:expire-passwords')
            ->setDescription('Disable users that have expired passwords and send them a notification')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $producer = $container->get('oro_message_queue.client.message_producer');
        $producer->send(Topics::FORCE_EXPIRED_PASSWORDS, []);

        $output->writeln('<info>Password expiration has been queued.</info>');
    }
}
