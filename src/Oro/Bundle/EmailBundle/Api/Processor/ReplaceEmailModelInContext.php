<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Replaces the email model with the email entity in the context.
 */
class ReplaceEmailModelInContext implements ProcessorInterface
{
    public const EMAIL_MODEL = '_email_model';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $emailModel = $context->getData();
        if ($emailModel instanceof EmailModel) {
            $context->setData($emailModel->getEntity());
            // store the model to the context to be able to add additional processors to handle submitted data
            $context->set(self::EMAIL_MODEL, $emailModel);
        }
    }
}
