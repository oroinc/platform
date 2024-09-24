<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables email users creation via "included" section of "update" action for an email resource.
 */
class DisableIncludedEmailUsersCreation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // the context does not have included entities
            return;
        }

        foreach ($includedEntities as $entity) {
            if (!is_a($includedEntities->getClass($entity), EmailUser::class, true)) {
                continue;
            }
            $entityData = $includedEntities->getData($entity);
            if ($entityData->isExisting()) {
                continue;
            }
            $context->addError(
                Error::createValidationError(
                    Constraint::REQUEST_DATA,
                    'An email user cannot be created via email API resource. Use API resource to create an email user.'
                )->setSource(ErrorSource::createByPointer($entityData->getPath()))
            );
        }
    }
}
