<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\SetEntityIdToContext;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a translation identifier into the context.
 */
class SetTranslationIdToContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(SetEntityIdToContext::OPERATION_NAME)) {
            // the entity identifier was already set
            return;
        }

        if ($context->isExisting()) {
            // the setting of an entity identifier to the context is needed only for a new entity
            return;
        }

        $entity = $context->getResult();
        if (!$entity instanceof TranslationKey) {
            return;
        }

        $context->setId(
            TranslationIdUtil::buildTranslationId($entity->getId(), $context->getRequestData()['languageCode'])
        );
        $context->setProcessed(SetEntityIdToContext::OPERATION_NAME);
    }
}
