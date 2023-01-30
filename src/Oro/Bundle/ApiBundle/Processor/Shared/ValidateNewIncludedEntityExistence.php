<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that `include` section has no new entities in specified associations
 * related to the entity this processor is used.
 */
class ValidateNewIncludedEntityExistence implements ProcessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;
    private TranslatorInterface $translator;
    /** @var array [association_name_in_entity => is_collection, ...] */
    private array $associations;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     * @param TranslatorInterface       $translator
     * @param array                     $associations [association_name_in_entity => is_collection, ...]
     */
    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        TranslatorInterface $translator,
        array $associations
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->translator = $translator;
        $this->associations = $associations;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        foreach ($this->associations as $associationName => $isCollection) {
            $propertyValue = $this->propertyAccessor->getValue($context->getData(), $associationName);
            if ($isCollection) {
                foreach ($propertyValue as $propertyEntity) {
                    $this->validateEntity($propertyEntity, $includedEntities);
                }
            } elseif (null !== $propertyValue) {
                $this->validateEntity($propertyValue, $includedEntities);
            }
        }
    }

    private function validateEntity(object $propertyEntity, IncludedEntityCollection $includedEntities): void
    {
        $propertyEntityData = $includedEntities->getData($propertyEntity);
        if ($propertyEntityData && !$propertyEntityData->isExisting()) {
            FormUtil::addNamedFormError(
                $propertyEntityData->getForm(),
                'new included entity existence constraint',
                $this->translator->trans('oro.api.new_entity_in_includes', [], 'validators')
            );
        }
    }
}
