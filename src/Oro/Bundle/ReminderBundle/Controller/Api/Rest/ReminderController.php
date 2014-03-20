<?php

namespace Oro\Bundle\ReminderBundle\Controller\Api\Rest;

use Symfony\Component\HttpKernel\Exception\HttpException;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * @NamePrefix("oro_api_")
 */
class ReminderController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get requested reminders list
     *
     * @throws HttpException
     */
    public function getRequestedAction()
    {
        $user = $this->getUser();

        if ($user == null) {
            return $this->handleView($this->view('User not logged in.', Codes::HTTP_UNAUTHORIZED));
        }

        /**
         * @var MessageParamsProvider
         */
        $paramsProvider = $this->get('oro_reminder.web_socket.message_params_provider');
        $reminders      = $this->getReminderRepository()->findRequestedReminders($user);

        $view = $this->view($paramsProvider->getMessageParamsForReminders($reminders), Codes::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Update reminder, set shown status
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postShownAction()
    {
        $user = $this->getUser();

        if ($user == null) {
            return $this->handleView($this->view('User not logged in.', Codes::HTTP_UNAUTHORIZED));
        }

        $userId = $user->getId();

        $reminders = $this->getReminderRepository()->findReminders($this->getRequest()->get('ids', array()));

        /**
         * @var Reminder $reminder
         */
        foreach ($reminders as $reminder) {
            if ($reminder->getState() == Reminder::STATE_REQUESTED &&
                $reminder->getRecipient()->getId() == $userId
            ) {
                $reminder->setState(Reminder::STATE_SENT);
            }
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->handleView($this->view('', Codes::HTTP_OK));
    }

    /**
     * @return \Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->getDoctrine()->getRepository('OroReminderBundle:Reminder');
    }
}
