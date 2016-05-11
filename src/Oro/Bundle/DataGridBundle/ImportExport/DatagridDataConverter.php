<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Tools\ColumnsHelper;

use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;

class DatagridDataConverter implements DataConverterInterface, ContextAwareInterface
{
    /**
     * @var ServiceLink
     */
    protected $gridManagerLink;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormatterProvider
     */
    protected $formatterProvider;

    /**
     * @var ColumnsHelper
     */
    protected $columnsHelper;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var TypeFormatterInterface[]
     */
    protected $formatters = [];

    /**
     * @param ServiceLink         $gridManagerLink
     * @param TranslatorInterface $translator
     * @param ColumnsHelper       $columnsHelper
     * @param FormatterProvider   $formatterProvider
     */
    public function __construct(
        ServiceLink $gridManagerLink,
        TranslatorInterface $translator,
        ColumnsHelper $columnsHelper,
        FormatterProvider $formatterProvider
    ) {
        $this->gridManagerLink   = $gridManagerLink;
        $this->translator        = $translator;
        $this->columnsHelper     = $columnsHelper;
        $this->formatterProvider = $formatterProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        if ($this->context->getValue('columns')) {
            $columns = $this->context->getValue('columns');
        } elseif ($this->context->hasOption('gridName')) {
            $gridName   = $this->context->getOption('gridName');
            $gridConfig = $this->gridManagerLink->getService()->getConfigurationForGrid($gridName);
            $columns    = $gridConfig->offsetGet('columns');
        } else {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export processor must contain "gridName" or "columns" options.'
            );
        }

        if ($this->context->hasOption('gridParameters')) {
            $gridParams = $this->context->getOption('gridParameters');
            if ($gridParams->has(ColumnsExtension::COLUMNS_PARAM)) {
                $columnsParams = $gridParams->get(ColumnsExtension::COLUMNS_PARAM);
                $columns       = $this->columnsHelper->reorderColumns($columns, $columnsParams);
            }
        }

        $result = [];
        foreach ($columns as $columnName => $column) {
            if (isset($column['renderable']) && false === $column['renderable']) {
                continue;
            }

            $val  = isset($exportedRecord[$columnName]) ? $exportedRecord[$columnName] : null;
            $val  = $this->applyFrontendFormatting($val, $column);
            $result[$this->translator->trans($column['label'])] = $val;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        throw new RuntimeException('The convertToImportFormat method is not implemented.');
    }

    /**
     * @param mixed $val
     * @param array $options
     *
     * @return string|null
     */
    protected function applyFrontendFormatting($val, $options)
    {
        if (null !== $val) {
            $frontendType = isset($options['frontend_type']) ? $options['frontend_type'] : null;

            $formatter = $this->getFormatterForType($frontendType);
            if ($formatter) {
                $val = $formatter->formatType($val, $frontendType);
            } else {
                switch ($frontendType) {
                    case PropertyInterface::TYPE_SELECT:
                        if (isset($options['choices'][$val])) {
                            $val = $this->translator->trans($options['choices'][$val]);
                        }
                        break;
                    case PropertyInterface::TYPE_MULTI_SELECT:
                        if (is_array($val) && count($val)) {
                            $val = implode(',', array_map(function ($value) use ($options) {
                                return array_key_exists($value, $options['choices'])
                                    ? $options['choices'][$value]
                                    : '';
                            }, $val));
                        }
                        break;
                    case PropertyInterface::TYPE_HTML:
                        $val = $this->formatHtmlFrontendType(
                            $val,
                            isset($options['export_type']) ? $options['export_type'] : null
                        );
                        break;
                }
            }
        }

        return $val;
    }

    /**
     * Converts HTML to its string representation
     *
     * @param string $val
     * @param string $exportType
     *
     * @return string
     */
    protected function formatHtmlFrontendType($val, $exportType)
    {
        $result = trim(
            str_replace(
                "\xC2\xA0", // non-breaking space (&nbsp;)
                ' ',
                html_entity_decode(strip_tags($val))
            )
        );
        if ($exportType === 'list') {
            $result = preg_replace('/\s*\n\s*/', ';', $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param string $type
     *
     * @return TypeFormatterInterface
     */
    protected function getFormatterForType($type)
    {
        $formatType = $this->context->getOption(FormatterProvider::FORMAT_TYPE);
        if (isset($this->formatters[$formatType][$type])) {
            return $this->formatters[$formatType][$type];
        }
        $formatter                            = $this->formatterProvider->getFormatterFor($formatType, $type);
        $this->formatters[$formatType][$type] = $formatter;

        return $formatter;
    }
}
