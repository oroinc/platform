<?php

namespace Oro\Bundle\ReminderBundle\Twig;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReminderExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getSecurityTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * @return MessageParamsProvider
     */
    protected function getMessageParamsProvider()
    {
        return $this->container->get('oro_reminder.web_socket.message_params_provider');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine')->getManagerForClass(Reminder::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_reminder_get_requested_reminders_data',
                [$this, 'getRequestedRemindersData']
            )
        ];
    }

    /**
     * Get requested reminders
     *
     * @return string
     */
    public function getRequestedRemindersData()
    {
        /** @var User|null */
        $user = null;
        $token = $this->getSecurityTokenStorage()->getToken();
        if (null !== $token) {
            $user = $token->getUser();
        }

        if ($user instanceof User) {
            $reminders = $this->getEntityManager()
                ->getRepository(Reminder::class)
                ->findRequestedReminders($user);

            return $this->getMessageParamsProvider()->getMessageParamsForReminders($reminders);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder.subscriber';
    }
}
