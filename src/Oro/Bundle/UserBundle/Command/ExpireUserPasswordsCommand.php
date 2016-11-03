<?php

namespace Oro\Bundle\UserBundle\Command;

use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\UserBundle\Async\Topics;

class ExpireUserPasswordsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * Run command every hour
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
            ->setDescription('Queues to disable users that have expired passwords and send them a notification');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $producer = $container->get('oro_message_queue.client.message_producer');
        /** @var UserRepository $repo */
        $repo = $container->get('doctrine')->getEntityManagerForClass(User::class)->getRepository(User::class);

        $userIds = $repo->getExpiredPasswordUserIds();

        foreach ($userIds as $userId) {
            $producer->send(Topics::EXPIRE_USER_PASSWORDS, $userId);
        }

        $output->writeln(sprintf('<info>Password expiration has been queued for %d users.</info>', count($userIds)));
    }
}
