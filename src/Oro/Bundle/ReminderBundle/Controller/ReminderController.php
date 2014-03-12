<?php

namespace Oro\Bundle\ReminderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @throws NotFoundHttpException
     * @return Response
     */
    public function reminderShowedAction()
    {
        $user = $this->getUser();

        if ($user == null) {
            throw new NotFoundHttpException('User not found');
        }

        $userId = $user->getId();

        $remindersId = $this->getRequest()->get('ids', array());

        $reminders = $this->getDoctrine()
            ->getRepository('OroReminderBundle:Reminder')
            ->findReminders($remindersId);

        /**
         * @var Reminder $reminder
         */
        foreach ($reminders as $reminder) {
            if ($reminder->getState() == Reminder::STATE_REQUESTED && $reminder->getRecipient()->getId() == $userId) {
                $reminder->setState(Reminder::STATE_SENT);
            }
        }

        $this->getDoctrine()->getManager()->flush();

        return new Response();
    }
}
