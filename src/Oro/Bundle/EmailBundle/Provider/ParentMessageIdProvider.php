<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;

/**
 * Provides parent message id for reply email models.
 */
class ParentMessageIdProvider
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getParentMessageIdToReply(EmailModel $emailModel): ?string
    {
        $parentEmailId = $emailModel->getParentEmailId();
        if (!$parentEmailId || $emailModel->getMailType() !== EmailModel::MAIL_TYPE_REPLY) {
            return null;
        }

        return $this->managerRegistry
            ->getRepository(Email::class)
            ->findMessageIdByEmailId($parentEmailId);
    }
}
