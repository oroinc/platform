<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Model\EnumOption;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationCreate;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates the enum option translation model ID in the context data.
 * A new translation key receives its ID only after it is flushed to the database,
 * so the model's ID must be updated afterward.
 */
class UpdateIncludedEnumOptionTranslationModelIdentifier implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities || $includedEntities->getPrimaryEntityClass() !== EnumOption::class) {
            return;
        }

        $model = $context->getData();
        if ($model instanceof TranslationCreate) {
            $model->synchronizeNewTranslationKeyId();
        }
    }
}
