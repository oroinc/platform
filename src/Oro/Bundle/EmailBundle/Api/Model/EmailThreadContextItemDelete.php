<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

use Oro\Bundle\ApiBundle\Model\EntityHolderInterface;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;

/**
 * Represents the email thread context item for "delete" action.
 */
class EmailThreadContextItemDelete implements EntityHolderInterface
{
    private string $id;
    private ?object $entity;
    private ?EmailEntity $emailEntity;

    public function __construct(
        string $id,
        object $entity,
        EmailEntity $emailEntity
    ) {
        $this->id = $id;
        $this->entity = $entity;
        $this->emailEntity = $emailEntity;
    }

    public function getId(): string
    {
        return $this->id;
    }

    #[\Override]
    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getEmailEntity(): EmailEntity
    {
        return $this->emailEntity;
    }
}
