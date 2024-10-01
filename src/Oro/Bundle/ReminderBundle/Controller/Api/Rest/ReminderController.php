<?php

namespace Oro\Bundle\ReminderBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to update reminders.
 */
class ReminderController extends AbstractFOSRestController
{
    /**
     * Update reminder, set shown status
     * @param Request $request
     * @return Response
     */
    public function postShownAction(Request $request)
    {
        $user = $this->getUser();

        if ($user == null) {
            return $this->handleView($this->view('User not logged in.', Response::HTTP_UNAUTHORIZED));
        }

        $userId = $user->getId();

        $reminders = $this->getReminderRepository()->findReminders($request->get('ids', []));

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

        $this->container->get('doctrine')->getManager()->flush();

        return $this->handleView($this->view('', Response::HTTP_OK));
    }

    /**
     * @return ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->container->get('doctrine')->getRepository(Reminder::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
