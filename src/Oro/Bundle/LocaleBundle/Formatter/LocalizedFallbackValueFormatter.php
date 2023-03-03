<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Formats LocalizedFallBackValue variables in email templates.
 */
class LocalizedFallbackValueFormatter implements FormatterInterface
{
    private const DEFAULT_VALUE = 'N/A';

    private DoctrineHelper $doctrineHelper;
    private LocalizationHelper $localizationHelper;
    private TranslatorInterface $translator;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        TranslatorInterface $translator,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
        $this->localizationHelper = $localizationHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = []): string
    {
        $fieldName = $formatterArguments['associationName'] ?? null;
        if ($fieldName && $this->isSupported(ClassUtils::getClass($value), $fieldName)) {
            if ($this->propertyAccessor->isReadable($value, $fieldName)) {
                $value = $this->propertyAccessor->getValue($value, $fieldName);

                return $this->localizationHelper->getLocalizedValue($value) ?? $this->getDefaultValue();
            }
        }


        return $this->getDefaultValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue(): string
    {
        return $this->translator->trans(self::DEFAULT_VALUE);
    }

    private function isSupported(string $className, string $associationName): bool
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($className);
        if (!$metadata->hasAssociation($associationName)) {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);

        return
            $mapping['type'] & ClassMetadata::TO_MANY
            && \is_a($mapping['targetEntity'], AbstractLocalizedFallbackValue::class, true);
    }
}
