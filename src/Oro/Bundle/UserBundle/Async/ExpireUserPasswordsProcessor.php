<?php

namespace Oro\Bundle\UserBundle\Async;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Message Queue processor to expire passwords of users
 * $message should include an (int) userId or (array) userIds
 */
class ExpireUserPasswordsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TEMPLATE_NAME = 'force_reset_password';

    /** @var LoggerInterface */
    protected $logger;

    /** @var EmailNotificationManager */
    protected $notificationManager;

    /** @var UserManager */
    protected $userManager;

    /** @var RegistryInterface */
    protected $doctrine;

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
        return [Topics::EXPIRE_USER_PASSWORDS];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $userIds = (array) json_decode($message->getBody());
        $template = $this->getEmailTemplate();
        if (!$template) {
            $this->logError('Cannot find email template "%s"', self::TEMPLATE_NAME);

            return self::ACK;
        }

        $users = $this->getUserRepository()->findBy(['id' => $userIds, 'loginDisabled' => false, 'enabled' => true]);

        foreach ($users as $user) {
            /** @var User $user */
            $userEmail = $user->getEmail();

            $user->setConfirmationToken($user->generateToken());
            $user->setLoginDisabled(true);
            $this->userManager->updateUser($user, false);

            try {
                $passResetNotification = new EmailNotification($template, [$userEmail]);
                $this->notificationManager->process($user, [$passResetNotification]);
            } catch (\Exception $e) {
                $this->logError(sprintf('Sending expired password email to %s failed.', $userEmail), $e->getMessage());
            }
        }

        $this->getUserEntityManager()->flush();

        return self::ACK;
    }

    /**
     * Log error if logger is enabled
     *
     * @param $msg
     * @param null $data
     */
    protected function logError($msg, $data = null)
    {
        if ($this->logger) {
            $this->logger->error($msg, $data);
        }
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getUserEntityManager()
    {
        return $this->doctrine->getEntityManagerForClass(User::class);
    }

    /**
     * @return \Oro\Bundle\UserBundle\Entity\Repository\UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getUserEntityManager()->getRepository(User::class);
    }

    /**
     * get Instance of the email template
     *
     * @return EmailTemplateInterface
     */
    protected function getEmailTemplate()
    {
        return $this->doctrine->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class)
            ->findOneBy(['name' => self::TEMPLATE_NAME]);
    }
}
