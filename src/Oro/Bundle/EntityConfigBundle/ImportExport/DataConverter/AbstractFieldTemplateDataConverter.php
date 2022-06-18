<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

/**
 * Data converter that converts imported record to the format that is used to deserialize the entity from the array.
 */
abstract class AbstractFieldTemplateDataConverter extends EntityFieldDataConverter
{
    protected FieldTypeProvider $fieldTypeProvider;

    public function __construct(FieldTypeProvider $fieldTypeProvider)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getBackendHeader()
    {
        $header = $this->getMainHeaders();

        foreach ($this->fieldTypeProvider->getSupportedFieldTypes() as $fieldType) {
            $properties = $this->getFieldProperties($fieldType);

            foreach ($properties as $scope => $fields) {
                foreach ($fields as $code => $config) {
                    $field = sprintf('%s.%s', $scope, $code);
                    if (\in_array($field, $header, true)) {
                        continue;
                    }

                    if (isset($config['import_export']['import_template']['value'])
                        && \is_array($config['import_export']['import_template']['value'])
                    ) {
                        $header = array_merge($header, $this->collectHeadersForArrayTemplateValue($field, $config));
                    } else {
                        $header[] = $field;
                    }
                }
            }
        }

        return $header;
    }

    protected function getFieldProperties(string $fieldType): array
    {
        return $this->fieldTypeProvider->getFieldProperties($fieldType);
    }

    /**
     * {@inheritDoc}
     */
    protected function fillEmptyColumns(array $header, array $data)
    {
        $dataDiff = array_diff(array_keys($data), $header);
        $data = array_diff_key($data, array_flip($dataDiff));

        return parent::fillEmptyColumns($header, $data);
    }

    abstract protected function getMainHeaders(): array;

    private function collectHeadersForArrayTemplateValue(string $field, array $config): array
    {
        $headers = [];
        foreach ($config['import_export']['import_template']['value'] as $index => $data) {
            if (\is_array($data)) {
                foreach (array_keys($data) as $parameterName) {
                    $headers[] = sprintf('%s.%s.%s', $field, $index, $parameterName);
                }
            } else {
                $headers[] = sprintf('%s.%s', $field, $index);
            }
        }

        return $headers;
    }
}
