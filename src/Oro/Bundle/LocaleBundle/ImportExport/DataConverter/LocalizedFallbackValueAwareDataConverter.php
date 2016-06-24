<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\DataConverter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;

class LocalizedFallbackValueAwareDataConverter extends PropertyPathTitleDataConverter
{
    const FIELD_VALUE = 'value';
    const FIELD_FALLBACK = 'fallback';
    const DEFAULT_LOCALIZATION = 'default';

    /** @var string */
    protected $localizationClassName;

    /** @var string */
    protected $localizedFallbackValueClassName;

    /** @var string[] */
    private $names;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     * @return PropertyPathTitleDataConverter
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param string $localizationClassName
     */
    public function setLocalizationClassName($localizationClassName)
    {
        $this->localizationClassName = $localizationClassName;
    }

    /**
     * @param string $localizedFallbackValueClassName
     */
    public function setLocalizedFallbackValueClassName($localizedFallbackValueClassName)
    {
        $this->localizedFallbackValueClassName = $localizedFallbackValueClassName;
    }

    /**
     * @return string[]
     */
    protected function getNames()
    {
        if (null === $this->names) {
            /* @var $localizationRepository LocalizationRepository */
            $localizationRepository = $this->registry->getRepository($this->localizationClassName);
            $this->names = ArrayUtil::arrayColumn($localizationRepository->getNames(), 'name');
            array_unshift($this->names, LocalizationCodeFormatter::DEFAULT_LOCALIZATION);
        }

        return $this->names;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelatedEntityRulesAndBackendHeaders(
        $entityName,
        $singleRelationDeepLevel,
        $multipleRelationDeepLevel,
        $field,
        $fieldHeader,
        $fieldOrder
    ) {
        if (is_a($field['related_entity_name'], $this->localizedFallbackValueClassName, true)) {
            $localizationCodes = $this->getNames();
            $targetField = $this->fieldHelper->getConfigValue($entityName, $field['name'], 'fallback_field', 'string');
            $fieldName = $field['name'];
            $rules = [];
            $backendHeaders = [];

            $subOrder = 0;
            foreach ($localizationCodes as $localizationCode) {
                $frontendHeader = $this->getHeader(
                    $fieldName,
                    $localizationCode,
                    self::FIELD_FALLBACK,
                    $this->relationDelimiter
                );
                $backendHeader = $this->getHeader(
                    $fieldName,
                    $localizationCode,
                    self::FIELD_FALLBACK,
                    $this->convertDelimiter
                );
                $rules[$frontendHeader] = [
                    'value' => $backendHeader,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++
                ];
                $backendHeaders[] = $rules[$frontendHeader];

                $frontendHeader = $this->getHeader(
                    $fieldName,
                    $localizationCode,
                    self::FIELD_VALUE,
                    $this->relationDelimiter
                );
                $backendHeader = $this->getHeader(
                    $fieldName,
                    $localizationCode,
                    $targetField,
                    $this->convertDelimiter
                );

                $rules[$frontendHeader] = [
                    'value' => $backendHeader,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++
                ];
                $backendHeaders[] = $rules[$frontendHeader];
            }

            return [$rules, $backendHeaders];
        }

        return parent::getRelatedEntityRulesAndBackendHeaders(
            $entityName,
            $singleRelationDeepLevel,
            $multipleRelationDeepLevel,
            $field,
            $fieldHeader,
            $fieldOrder
        );
    }

    /**
     * @param string $fieldName
     * @param string $identity
     * @param string $targetFieldName
     * @param string $delimiter
     * @return string
     */
    protected function getHeader($fieldName, $identity, $targetFieldName, $delimiter)
    {
        return $fieldName . $delimiter . LocalizationCodeFormatter::formatName($identity) .
            $delimiter . $targetFieldName;
    }
}
