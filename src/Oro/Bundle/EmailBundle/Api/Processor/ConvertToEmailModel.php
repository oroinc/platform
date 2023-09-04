<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts loaded Email entity to the Email model.
 */
class ConvertToEmailModel implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext|FormContext $context */

        if (!$context->hasResult()) {
            // the entity was not loaded
            return;
        }

        $email = $context->getResult();
        if (!$email instanceof Email) {
            // the entity was already converted to the model
            return;
        }

        $emailModel = new EmailModel();
        $emailModel->setEntity($email);

        $context->setResult($emailModel);
        // disable entity mapping
        $context->setEntityMapper(null);
    }
}
