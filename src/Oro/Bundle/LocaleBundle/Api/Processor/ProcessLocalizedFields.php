<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PropertyAccess\PropertyAccessor;

class ProcessLocalizedFields implements ProcessorInterface
{
    /** @var  LocalizationHelper */
    protected $localizationHelper;

    /** @var  DoctrineHelper */
    protected $doctrinHelper;

    /** @var  PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param LocalizationHelper $localizationHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(LocalizationHelper $localizationHelper, DoctrineHelper $doctrineHelper)
    {
        $this->localizationHelper = $localizationHelper;
        $this->doctrinHelper = $doctrineHelper;
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CreateContext $context */

        /** @var EntityMetadata $entityMetadata */
        $entityMetadata = $context->getMetadata();
        if (!$entityMetadata instanceof EntityMetadata) {
            return;
        }

        $em = $this->doctrinHelper->getEntityManagerForClass(LocalizedFallbackValue::class);
        $associations = $entityMetadata->getAssociations();
        $entity = $context->getForm()->getData();

        foreach ($associations as $relationName => $association) {
            /** @var AssociationMetadata $association */
            if ($association->getAssociationType() === RelationType::MANY_TO_MANY
                && $association->getTargetClassName() === LocalizedFallbackValue::class
            ) {
                $this->addMissingLocalizedValues($entity, $relationName, $em);
            }
        }

        $em->flush();
    }

    /**
     * @param object $entity
     * @param string $relationName
     */
    protected function addMissingLocalizedValues($entity, $relationName, $em)
    {
        $localizations = $this->localizationHelper->getLocalizations();
        $localizedValues = $this->propertyAccessor->getValue($entity, $relationName);
        $missingLocalization = $this->getMissingLocalizedValues($localizedValues, $localizations);

        /**
         * @var string $localizationId
         * @var Localization $localization
         */
        foreach ($missingLocalization as $localizationId => $localization) {
            $this->createLocalizedFallbackValue($entity, $relationName, $em, $localization);
        }
    }

    /**
     * @param LocalizedFallbackValue[] $localizedValues
     * @param array $localizations
     * @return mixed
     */
    protected function getMissingLocalizedValues($localizedValues, $localizations)
    {
        $hasDefault = false;
        /** @var LocalizedFallbackValue $value */
        foreach ($localizedValues as $value) {
            if (!is_object($value->getLocalization())) {
                $hasDefault = true;
                continue;
            }

            $localizationId = $value->getLocalization()->getId();
            if (in_array($localizationId, array_keys($localizations))) {
                unset($localizations[$localizationId]);
            }
        }

        if (!$hasDefault) {
            $localizations['default'] = null;
        }

        return $localizations;
    }

    /**
     * @param object $entity
     * @param string $relationName
     * @param EntityManager $em
     * @param Localization|null $localization
     */
    protected function createLocalizedFallbackValue(
        $entity,
        $relationName,
        EntityManager $em,
        Localization $localization = null
    ) {
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setLocalization($localization);
        if ($localization !== null) {
            $parent = $localization->getParentLocalization();
            $localizedFallbackValue->setFallback(
                $parent !== null ? FallbackType::PARENT_LOCALIZATION : FallbackType::SYSTEM
            );
        }
        $this->propertyAccessor->setValue($entity, $relationName, $localizedFallbackValue);

        $em->persist($localizedFallbackValue);
    }
}
