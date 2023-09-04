<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "email" and "emailBody" associations for email users and email attachments
 * from "included" section if these associations were not set.
 */
class UpdateIncludedEmailAssociations implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // no included entities
            return;
        }

        /** @var Email $email */
        $email = $context->getData();
        foreach ($includedEntities as $entity) {
            $entityClass = $includedEntities->getClass($entity);
            if (!$entityClass) {
                continue;
            }
            if (is_a($entityClass, EmailUser::class, true) && null === $entity->getEmail()) {
                $entity->setEmail($email);
            } elseif (is_a($entityClass, EmailAttachment::class, true) && null === $entity->getEmailBody()) {
                $entity->setEmailBody($email->getEmailBody());
            }
        }
    }
}
