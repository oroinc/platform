<?php

namespace Oro\Bundle\ImportExportBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that ACL protected entity have owner and organization.
 */
class ValidateOwnerListener
{
    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var ConfigurableTableDataConverter */
    private $configurableDataConverter;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        PropertyAccessorInterface $propertyAccessor,
        ConfigurableTableDataConverter $configurableDataConverter,
        TranslatorInterface $translator
    ) {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->configurableDataConverter = $configurableDataConverter;
        $this->translator = $translator;
    }

    public function onProcessAfter(StrategyEvent $event): void
    {
        $entity = $event->getEntity();
        if (null === $entity) {
            return;
        }

        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity));
        if (false === $ownershipMetadata->hasOwner()) {
            return;
        }

        // check owner field
        $context = $event->getContext();
        $ownerFieldName = $ownershipMetadata->getOwnerFieldName();
        if (null === $this->propertyAccessor->getValue($entity, $ownerFieldName)) {
            $context->addError($this->getErrorString($entity, $ownerFieldName));
        }

        // check organization field
        if (($ownershipMetadata->isUserOwned() || $ownershipMetadata->isBusinessUnitOwned())
            && null === $this->propertyAccessor->getValue($entity, $ownershipMetadata->getOrganizationFieldName())
        ) {
            $context->addError($this->getErrorString($entity, $ownershipMetadata->getOrganizationFieldName()));
        }
    }

    private function getErrorString(object $entity, string $propertyPath): string
    {
        if ($propertyPath && is_object($entity)) {
            $fieldHeader = $this->configurableDataConverter->getFieldHeaderWithRelation(
                ClassUtils::getClass($entity),
                $propertyPath
            );
            $propertyPath = ($fieldHeader ?: $propertyPath) . ': ';
        }

        return $propertyPath . $this->translator->trans('This value should not be blank.', [], 'validators');
    }
}
