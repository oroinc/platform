<?php

namespace Oro\Bundle\UserBundle\Async;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ExpireUserPasswordsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TEMPLATE_NAME = 'force_reset_password';
    const BATCH_SIZE = 100;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EmailNotificationManager */
    protected $notificationManager;

    /** @var UserManager */
    protected $userManager;

    /** @var RegistryInterface */
    protected $doctrine;

    /** @var EmailTemplateInterface */
    protected $template;

    /**
     * @param EmailNotificationManager $notificationManager
     * @param UserManager $userManager
     * @param RegistryInterface $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailNotificationManager $notificationManager,
        UserManager $userManager,
        RegistryInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->notificationManager = $notificationManager;

        $this->userManager = $userManager;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::FORCE_EXPIRED_PASSWORDS];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $this->template = $this->doctrine->getManagerForClass('OroEmailBundle:EmailTemplate')
            ->getRepository('OroEmailBundle:EmailTemplate')
            ->findOneBy(['name' => self::TEMPLATE_NAME]);

        $users = $this->findExpiredPasswordUsersQb()
            ->getQuery()
            ->iterate();

        $count = 0;
        $batch = [];
        while ($user = $users->next()) {
            $batch[] = $user;
            if (++$count % self::BATCH_SIZE === 0) {
                $this->processBatch($batch);
                $batch = [];
            }
        }

        $this->processBatch($batch);

        return self::ACK;
    }

    /**
     * Process a batch of users
     *
     * @param array $users
     */
    protected function processBatch($users = [])
    {
        if (0 === count($users)) {
            return;
        }

        foreach ($users as $user) {
            $userEmail = $user->getEmail();

            $user->setConfirmationToken($user->generateToken());
            $user->setLoginDisabled(true);
            $this->userManager->updateUser($user, false);

            try {
                $passResetNotification = new EmailNotification($this->template, [$userEmail]);;
                $this->notificationManager->process($user, [$passResetNotification]);
                if (null !== $this->logger) {
                    $this->logger->debug(sprintf('Sending expired password email to %s', $userEmail), $user->getId());
                }
            } catch (\Exception $e) {
                if (null !== $this->logger) {
                    $this->logger->error(sprintf('Sending expired password email to %s failed.', $userEmail));
                    $this->logger->error($e->getMessage());
                }
            }
        }

        $this->getUserEntityManager()->flush();
    }

    /**
     * @return \Doctrine\ORM\EntityManager|null
     */
    protected function getUserEntityManager()
    {
        return $this->doctrine->getEntityManagerForClass(User::class);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function findExpiredPasswordUsersQb()
    {
        return $this->getUserEntityManager()->getRepository(User::class)->findExpiredPasswordUsersQb();
    }
}
