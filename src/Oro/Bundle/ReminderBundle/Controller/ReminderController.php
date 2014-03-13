<?php

namespace Oro\Bundle\ReminderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
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

        $reminderId = $this->getRequest()->get('id', null);

        if (!$reminderId) {
            return new Response('', 200);
        }

        $reminders = $this->getReminderRepository()->findReminders(array($reminderId));

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

    /**
     * @Route("/requested", name="oro_reminder_requested")
     * @throws HttpException
     * @return JsonResponse
     */
    public function requestedRemindersAction()
    {
        $user = $this->getUser();

        if ($user == null) {
            throw new HttpException(401, 'User not logged in.');
        }

        /**
         * @var MessageParamsProvider
         */
        $paramsProvider = $this->get('oro_reminder.web_socket.message_params_provider');
        $reminders = $this->getReminderRepository()->findRequestedReminders($user);

        return new JsonResponse($paramsProvider->getMessageParamsForReminders($reminders));
    }

    /**
     * @return \Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->getDoctrine()->getRepository('OroReminderBundle:Reminder');
    }
}
