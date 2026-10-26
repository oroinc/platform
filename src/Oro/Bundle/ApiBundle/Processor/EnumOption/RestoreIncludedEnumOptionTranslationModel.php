<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Model\EnumOption;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationModel;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Restores the enum option translation model in the context data.
 * The standard translation processor replaces the event data with a TranslationKey.
 * Without this processor, the included-entity handler would replace the model
 * in its collection with that key too. The form still holds the model,
 * so we restore it for LoadNormalizedIncludedEntities to read its language-specific ID after saving.
 */
class RestoreIncludedEnumOptionTranslationModel implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities || $includedEntities->getPrimaryEntityClass() !== EnumOption::class) {
            return;
        }

        $model = $context->getForm()->getData();
        if ($model instanceof TranslationModel) {
            $context->setData($model);
        }
    }
}
