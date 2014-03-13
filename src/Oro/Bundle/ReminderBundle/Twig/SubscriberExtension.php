<?php

namespace Oro\Bundle\ReminderBundle\Twig;

use Symfony\Component\Security\Core\SecurityContext;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;

class SubscriberExtension extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    public function __construct(
        EntityManager $entityManager,
        SecurityContext $securityContext,
        MessageParamsProvider $messageParamsProvider
    ) {
        $this->entityManager = $entityManager;
        $this->securityContext = $securityContext;
        $this->messageParamsProvider = $messageParamsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_reminder_get_requested_reminders', array($this, 'getRequestedReminders'))
        );
    }

    /**
     * Twig function callback
     *
     * @return string
     */
    public function getRequestedReminders()
    {
        /**
         * @var User|null
         */
        $user = $this->securityContext->getToken()->getUser();

        $remindersList = array();

        if (is_object($user) && $user instanceof User) {
            $reminders = $this->entityManager->getRepository('Oro\Bundle\ReminderBundle\Entity\Reminder')
                ->findRequestedReminders($user);

            /**
             * @var Reminder $reminder
             */
            foreach ($reminders as $reminder) {
                $remindersList[] = $this->messageParamsProvider->getMessageParams($reminder);
            }
        }

        return json_encode($remindersList);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'oro_reminder.subscriber';
    }
}
