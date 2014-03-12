<?php

namespace Oro\Bundle\ReminderBundle\Twig;

use Symfony\Component\Security\Core\SecurityContext;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;

class ReminderExtension extends \Twig_Extension
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

    /**
     * @param EntityManager $entityManager
     * @param SecurityContext $securityContext
     * @param MessageParamsProvider $messageParamsProvider
     */
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
            new \Twig_SimpleFunction(
                'oro_reminder_get_requested_reminders_data',
                array($this, 'getRequestedRemindersData')
            )
        );
    }

    /**
     * Get requested reminders
     *
     * @return string
     */
    public function getRequestedRemindersData()
    {
        $result = array();
        $user = $this->securityContext->getToken()->getUser();

        if ($user) {
            $userId = $user->getId();
            $reminders = $this->entityManager->getRepository('Oro\Bundle\ReminderBundle\Entity\Reminder')
                ->findRequestedReminders($userId);

            foreach ($reminders as $reminder) {
                $result[] = $this->messageParamsProvider->getMessageParams($reminder);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder.subscriber';
    }
}
