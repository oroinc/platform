<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
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
    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param LocalizationHelper        $localizationHelper
     * @param DoctrineHelper            $doctrineHelper
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        DoctrineHelper $doctrineHelper,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        if (!$metadata->getAssociationMappings()) {
            return;
        }

        $entity = $form->getData();
        $em = $this->doctrineHelper->getEntityManagerForClass(LocalizedFallbackValue::class);
        $localizations = null;
        /** @var FormInterface $child */
        foreach ($form as $child) {
            $fieldName = $this->getFieldName($child);
            if ($this->isLocalizedFallbackValueAssociation($metadata, $fieldName)) {
                if (null === $localizations) {
                    $localizations = $this->localizationHelper->getLocalizations();
                }
                $this->addMissingLocalizedValues(
                    $this->propertyAccessor->getValue($entity, $fieldName),
                    $em,
                    $localizations
                );
            }
        }
    }

    /**
     * @param FormInterface $form
     *
     * @return string
     */
    private function getFieldName(FormInterface $form): string
    {
        $propertyPath = $form->getPropertyPath();
        if (null !== $propertyPath) {
            return (string)$propertyPath;
        }

        return $form->getName();
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return bool
     */
    private function isLocalizedFallbackValueAssociation(ClassMetadata $metadata, string $fieldName): bool
    {
        if (!$metadata->hasAssociation($fieldName)) {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($fieldName);

        return
            $mapping['type'] & ClassMetadata::MANY_TO_MANY
            && LocalizedFallbackValue::class === $mapping['targetEntity'];
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $associationValue
     * @param EntityManagerInterface              $em
     * @param Localization[]                      $localizations
     */
    private function addMissingLocalizedValues(
        Collection $associationValue,
        EntityManagerInterface $em,
        array $localizations
    ): void {
        $missingLocalizations = $this->getMissingLocalizations($associationValue, $localizations);
        foreach ($missingLocalizations as $localization) {
            $localizedFallbackValue = $this->createLocalizedFallbackValue($localization);
            $em->persist($localizedFallbackValue);
            $associationValue->add($localizedFallbackValue);
        }
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $associationValue
     * @param Localization[]                      $localizations
     *
     * @return Localization[]
     */
    private function getMissingLocalizations(Collection $associationValue, array $localizations): array
    {
        $hasDefault = false;
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

    /**
     * @param Localization|null $localization
     *
     * @return LocalizedFallbackValue
     */
    private function createLocalizedFallbackValue(?Localization $localization): LocalizedFallbackValue
    {
        $localizedFallbackValue = new LocalizedFallbackValue();
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
