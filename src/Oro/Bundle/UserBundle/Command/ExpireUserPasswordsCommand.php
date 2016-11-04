<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
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
            ->setDescription('Disable users with expired passwords and send them a notification')
            ->setHelp(
                'Command produces messages to MQ that disable all users with expired passwords.' .
                ' By default will produce one message per user.' .
                ' Set `--batch-size` to process multiple users per message.'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Size of the batch of expired users per MQ message',
                1
            );
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

        $batchSize = (int) $input->getOption('batch-size');
        if ($batchSize < 1) {
            throw new \InvalidArgumentException(sprintf('Invalid batch-size option "%s"', $batchSize));
        }

        $userIds = $repo->getExpiredPasswordUserIds(new \DateTime('now', new \DateTimeZone('UTC')));
        $batches = array_chunk($userIds, $batchSize, true);

        foreach ($batches as $batch) {
            $producer->send(Topics::EXPIRE_USER_PASSWORDS, $batch);
        }

        $output->writeln(sprintf('<info>Password expiration has been queued for %d users.</info>', count($userIds)));
    }
}
