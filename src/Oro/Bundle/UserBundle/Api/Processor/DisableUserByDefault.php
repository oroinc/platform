<?php

namespace Oro\Bundle\UserBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the "enabled" field of the User entity to false
 * if the data of this field was not specified in the request.
 */
class DisableUserByDefault implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $enabledFormField = FormUtil::findFormFieldByPropertyPath(
            $context->getForm(),
            'enabled'
        );

        if (null !== $enabledFormField && !$enabledFormField->isSubmitted()) {
            /** @var AbstractUser $user */
            $user = $context->getData();
            $user->setEnabled(false);
        }
    }
}
