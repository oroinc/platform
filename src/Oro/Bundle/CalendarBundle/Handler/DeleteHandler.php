<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\SoapBundle\Handler\DeleteHandler as BaseDeleteHandler;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class DeleteHandler extends BaseDeleteHandler
{
    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /**
     * @param EmailSendProcessor $emailSendProcessor
     */
    public function setEmailSendProcessor(EmailSendProcessor $emailSendProcessor)
    {
        $this->emailSendProcessor = $emailSendProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        $entity = $manager->find($id);
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $em = $manager->getObjectManager();
        $this->checkPermissions($entity, $em);
        $this->deleteEntity($entity, $em);
        $em->flush();
        $this->emailSendProcessor->sendDeleteEventNotification($entity);
    }

}
