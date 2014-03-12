<?php

namespace Oro\Bundle\ReminderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * @Route("/reminder")
 */
class ReminderController extends Controller
{

    /**
     * @Route("/shown", name="oro_reminder_shown")
     * @throws HttpException
     * @return Response
     */
    public function shownAction()
    {
        $user = $this->getUser();

        if ($user == null) {
            throw new HttpException(401, 'User not logged in.');
        }

        $userId = $user->getId();

        $reminders = $this->getDoctrine()
            ->getRepository('OroReminderBundle:Reminder')
            ->findReminders($this->getRequest()->get('ids', array()));

        foreach ($reminders as $reminder) {
            if ($reminder->getState() == Reminder::STATE_REQUESTED &&
                $reminder->getRecipient()->getId() == $userId
            ) {
                $reminder->setState(Reminder::STATE_SENT);
            }
        }

        $this->getDoctrine()->getManager()->flush();

        return new Response('', 200);
    }
}
