<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Symfony\Component\Translation\Translator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class DatagridDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /**
     * @var ManagerInterface
     */
    protected $gridManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @param ManagerInterface  $gridManager
     * @param Translator        $translator
     * @param NumberFormatter   $numberFormatter
     * @param DateTimeFormatter $dateTimeFormatter
     */
    public function __construct(
        ManagerInterface $gridManager,
        Translator $translator,
        NumberFormatter $numberFormatter,
        DateTimeFormatter $dateTimeFormatter
    ) {
        $this->gridManager       = $gridManager;
        $this->translator        = $translator;
        $this->numberFormatter   = $numberFormatter;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillEmptyColumns(array $header, array $data)
    {
        $gridName   = $this->context->getOption('gridName');
        $gridConfig = $this->gridManager->getConfigurationForGrid($gridName);

        $result = array();
        foreach ($header as $headerKey) {
            $val = $data[$headerKey];
            if (empty($val)) {
                $val = '';
            } else {
                $frontendType = $gridConfig->offsetGetByPath(sprintf('[columns][%s][frontend_type]', $headerKey));
                switch ($frontendType) {
                    case PropertyInterface::TYPE_DATE:
                        $val = $this->dateTimeFormatter->formatDate($val);
                        break;
                    case PropertyInterface::TYPE_DATETIME:
                        $val = $this->dateTimeFormatter->format($val);
                        break;
                    case PropertyInterface::TYPE_DECIMAL:
                        $val = $this->numberFormatter->formatDecimal($val);
                        break;
                    case PropertyInterface::TYPE_INTEGER:
                        $val = $this->numberFormatter->formatDecimal($val);
                        break;
                    case PropertyInterface::TYPE_PERCENT:
                        $val = $this->numberFormatter->formatPercent($val);
                        break;
                    default:
                        $val = $data[$headerKey];
                        break;
                }
            }
            $result[$headerKey] = $val;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $gridName   = $this->context->getOption('gridName');
        $gridConfig = $this->gridManager->getConfigurationForGrid($gridName);
        $columns    = $gridConfig->offsetGet('columns');

        $rules = [];
        foreach ($columns as $columnName => $column) {
            $rules[$this->translator->trans($column['label'])] = $columnName;
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $gridName   = $this->context->getOption('gridName');
        $gridConfig = $this->gridManager->getConfigurationForGrid($gridName);
        $columns    = $gridConfig->offsetGet('columns');

        $header = [];
        foreach ($columns as $columnName => $column) {
            $header[] = $columnName;
        }

        return $header;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}
