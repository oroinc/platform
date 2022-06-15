<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Makes sure that all LocalizedFallbackValue associations contain values for all localizations.
 */
class CompleteLocalizedValues implements ProcessorInterface
{
    private LocalizationManager $localizationManager;
    private DoctrineHelper $doctrineHelper;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        LocalizationManager $localizationManager,
        DoctrineHelper $doctrineHelper,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->localizationManager = $localizationManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!FormUtil::isSubmittedAndValid($form)) {
            return;
        }

        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        if (!$entityClass) {
            return;
        }

        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        if (!$metadata->getAssociationMappings()) {
            return;
        }

        $entity = $form->getData();
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        $localizations = null;
        /** @var FormInterface $child */
        foreach ($form as $child) {
            $fieldName = $this->getFieldName($child);
            if ($this->isLocalizedFallbackValueAssociation($metadata, $fieldName)) {
                if (null === $localizations) {
                    $localizations = $this->localizationManager->getLocalizations(null, false);
                }

                $oldValue = $this->propertyAccessor->getValue($entity, $fieldName);
                $added = $this->addMissingLocalizedValues(
                    $oldValue,
                    $em,
                    $localizations,
                    $metadata->getAssociationMapping($fieldName)
                );

                $this->propertyAccessor->setValue(
                    $entity,
                    $fieldName,
                    new ArrayCollection(array_merge($oldValue->toArray(), $added))
                );
            }
        }
    }

    private function getFieldName(FormInterface $form): string
    {
        $propertyPath = $form->getPropertyPath();
        if (null !== $propertyPath) {
            return (string)$propertyPath;
        }

        return $form->getName();
    }

    private function isLocalizedFallbackValueAssociation(ClassMetadata $metadata, string $fieldName): bool
    {
        if (!$metadata->hasAssociation($fieldName)) {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($fieldName);

        return
            $mapping['type'] & ClassMetadata::TO_MANY
            && is_a($mapping['targetEntity'], AbstractLocalizedFallbackValue::class, true);
    }

    private function addMissingLocalizedValues(
        Collection $associationValue,
        EntityManagerInterface $em,
        array $localizations,
        array $mapping
    ): array {
        $added = [];
        $missingLocalizations = $this->getMissingLocalizations($associationValue, $localizations);
        foreach ($missingLocalizations as $localization) {
            $localizedFallbackValue = $this->createLocalizedFallbackValue($localization, $mapping['targetEntity']);
            $em->persist($localizedFallbackValue);
            $added[] = $localizedFallbackValue;
        }

        return $added;
    }

    private function getMissingLocalizations(Collection $associationValue, array $localizations): array
    {
        $hasDefault = false;
        /** @var AbstractLocalizedFallbackValue $value */
        foreach ($associationValue as $value) {
            $localization = $value->getLocalization();
            if (null === $localization) {
                $hasDefault = true;
            } else {
                unset($localizations[$localization->getId()]);
            }
        }
        if (!$hasDefault) {
            $localizations[''] = null;
        }

        return $localizations;
    }

    private function createLocalizedFallbackValue(
        ?Localization $localization,
        string $className
    ): AbstractLocalizedFallbackValue {
        /** @var AbstractLocalizedFallbackValue $localizedFallbackValue */
        $localizedFallbackValue = new $className();
        if (null !== $localization) {
            $localizedFallbackValue->setLocalization($localization);
            $parent = $localization->getParentLocalization();
            $localizedFallbackValue->setFallback(
                null !== $parent ? FallbackType::PARENT_LOCALIZATION : FallbackType::SYSTEM
            );
        }

        return $localizedFallbackValue;
    }
}
