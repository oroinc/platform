<?php

namespace Oro\Bundle\ReminderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * @Route("/reminder")
 */
class ReminderController extends Controller
{

    /**
     * @Route("/change-reminder-state", name="oro_reminder_change_reminder_state")
     *
     * @return string
     */
    public function changeReminderState()
    {
        $userId = $this->get('security.context')
            ->getToken()
            ->getUser()
            ->getId();

        $remindersId = $this->getRequest()->get('ids', array());

        $reminders = $this->getDoctrine()
            ->getRepository('\Oro\Bundle\ReminderBundle\Entity\Reminder')
            ->findReminders($remindersId);

        /**
         * @var Reminder $reminder
         */
        foreach ($reminders as $reminder) {
            if ($reminder->getState() == Reminder::STATE_IN_PROGRESS && $reminder->getRecipient()
                    ->getId() == $userId
            ) {
                $reminder->setState(Reminder::STATE_SENT);
            } else {
                return new JsonResponse(array('result' => false, 'reason' => 'Incorrect recipient or reminder state'));
            }
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array('result' => true));
    }
}
