<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Validates that "email" association for EmailUser entity is set.
 */
class ValidateEmailUserEmailAssociationExists implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->isPrimaryEntityRequest()) {
            return;
        }

        /** @var EmailUser $emailUser */
        $emailUser = $context->getData();
        if (null === $emailUser->getEmail()) {
            FormUtil::addFormConstraintViolation($context->findFormField('email'), new NotBlank());
        }
    }
}
