<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;

class PasswordExpirationNotificationCommand extends ContainerAwareCommand implements CronCommandInterface
{
    private $notificationDays = [1, 3, 7];

    /**
     * Run command at 00:00 every day.
     *
     * @return string
     */
    public function getDefaultDefinition()
    {
        return '0 0 * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:password-expiration-notification')
            ->setDescription('Send password expiration notification to users')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $mailManager = $container->get('oro_notification.manager.email_notification');
        $doctrine = $container->get('oro_entity.doctrine_helper');

        $iteratorResult = $doctrine->getEntityRepository('OroUserBundle:User')
            ->getExpiringPasswordUsersQueryBuilder($this->notificationDays)
            ->getQuery()
            ->iterate();

        $template = $doctrine->getEntityRepository('OroEmailBundle:EmailTemplate')
            ->findOneBy(['name' => 'mandatory_password_change']);

        if (!$template) {
            $output->writeln('<error>Cannot find notification template</error>');

            return;
        }

        $usersCount = 0;
        while ($results = $iteratorResult->next()) {
            $user = $results[0];
            $notification = new EmailNotification($template, [$user->getEmail()]);
            $mailManager->process($user, [$notification]);
            $usersCount++;
        }

        $output->writeln(
            sprintf(
                '<info>Password expiration notification has been enqueued for %d users</info>',
                $usersCount
            )
        );
    }
}
