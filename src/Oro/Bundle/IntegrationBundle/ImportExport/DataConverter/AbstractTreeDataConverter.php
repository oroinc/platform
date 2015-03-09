<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;

abstract class AbstractTreeDataConverter extends IntegrationAwareDataConverter
{
    /**
     * @var array|DataConverterInterface[]
     */
    protected $nodeDataConverters = [];

    /**
     * @var array
     */
    protected $toManyDataConverters = [];

    /**
     * @param string $nodeKey
     * @param DataConverterInterface $dataConverter
     * @param bool $isToMany
     */
    public function addNodeDataConverter($nodeKey, DataConverterInterface $dataConverter, $isToMany = false)
    {
        $this->nodeDataConverters[$nodeKey] = $dataConverter;
        $this->toManyDataConverters[$nodeKey] = $isToMany;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        parent::setImportExportContext($context);

        foreach ($this->nodeDataConverters as $dataConverter) {
            if ($dataConverter instanceof IntegrationAwareDataConverter) {
                $dataConverter->setImportExportContext($context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        foreach ($this->nodeDataConverters as $nodeKey => $dataConverter) {
            if (!empty($importedRecord[$nodeKey]) && is_array($importedRecord[$nodeKey])) {
                if (empty($this->toManyDataConverters[$nodeKey])) {
                    $importedRecord[$nodeKey] = $dataConverter->convertToImportFormat(
                        $importedRecord[$nodeKey],
                        $skipNullValues
                    );
                } else {
                    foreach ($importedRecord[$nodeKey] as &$record) {
                        $record = $dataConverter->convertToImportFormat($record, $skipNullValues);
                    }
                }
            }
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $rules = $this->getHeaderConversionRules();
        $nodeData = [];
        foreach ($this->nodeDataConverters as $nodeKey => $dataConverter) {
            if (!empty($exportedRecord[$nodeKey]) && is_array($exportedRecord[$nodeKey])) {
                foreach ($exportedRecord[$nodeKey] as $key => $record) {
                    $dataKey = array_search($nodeKey, $rules, true);
                    if (false === $dataKey) {
                        $dataKey = $nodeKey;
                    }
                    $nodeData[$dataKey][$key] = $dataConverter->convertToExportFormat($record, $skipNullValues);
                }
            }

            unset($exportedRecord[$nodeKey]);
        }

        return array_merge(parent::convertToExportFormat($exportedRecord, $skipNullValues), $nodeData);
    }
}
