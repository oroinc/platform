<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\DataConverter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocaleCodeFormatter;

use OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository;

class LocalizedFallbackValueAwareDataConverter extends PropertyPathTitleDataConverter
{
    const FIELD_VALUE = 'value';
    const FIELD_FALLBACK = 'fallback';
    const DEFAULT_LOCALE = 'default';

    /** @var string */
    protected $localeClassName;

    /** @var string */
    protected $localizedFallbackValueClassName;

    /** @var string[] */
    private $localeCodes;

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
     * @param string $localeClassName
     */
    public function setLocaleClassName($localeClassName)
    {
        $this->localeClassName = $localeClassName;
    }

    /**
     * @param string $localizedFallbackValueClassName
     */
    public function setLocalizedFallbackValueClassName($localizedFallbackValueClassName)
    {
        $this->localizedFallbackValueClassName = $localizedFallbackValueClassName;
    }

    /** @return string[] */
    protected function getLocaleCodes()
    {
        if (null === $this->localeCodes) {
            /** @var LocaleRepository $localeRepository */
            $localeRepository = $this->registry->getRepository($this->localeClassName);
            $this->localeCodes = ArrayUtil::arrayColumn($localeRepository->getLocaleCodes(), 'code');
            array_unshift($this->localeCodes, LocaleCodeFormatter::DEFAULT_LOCALE);
        }

        return $this->localeCodes;
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
            $localeCodes = $this->getLocaleCodes();
            $targetField = $this->fieldHelper->getConfigValue($entityName, $field['name'], 'fallback_field', 'string');
            $fieldName = $field['name'];
            $rules = [];
            $backendHeaders = [];

            $subOrder = 0;
            foreach ($localeCodes as $localeCode) {
                $frontendHeader = $this->getHeader(
                    $fieldName,
                    $localeCode,
                    self::FIELD_FALLBACK,
                    $this->relationDelimiter
                );
                $backendHeader = $this->getHeader(
                    $fieldName,
                    $localeCode,
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
                    $localeCode,
                    self::FIELD_VALUE,
                    $this->relationDelimiter
                );
                $backendHeader = $this->getHeader(
                    $fieldName,
                    $localeCode,
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
        return $fieldName . $delimiter . LocaleCodeFormatter::formatName($identity) . $delimiter . $targetFieldName;
    }
}
