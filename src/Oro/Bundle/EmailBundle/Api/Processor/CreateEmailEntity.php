<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Creates new instance of Email entity and associates it with Email model.
 */
class CreateEmailEntity implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        $entity = $context->getResult();
        if ($entity instanceof Email) {
            $emailModel = new EmailModel();
            $emailModel->setEntity($entity);
            $context->setResult($emailModel);
        } elseif ($entity instanceof EmailModel && null === $entity->getEntity()) {
            $entity->setEntity(new Email());
        }
    }
}
