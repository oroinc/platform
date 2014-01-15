<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Symfony\Component\Translation\Translator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class DatagridDataConverter implements DataConverterInterface, ContextAwareInterface
{
    /**
     * @var ServiceLink
     */
    protected $gridManagerLink;

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
     *
     * @param ServiceLink       $gridManagerLink
     * @param Translator        $translator
     * @param NumberFormatter   $numberFormatter
     * @param DateTimeFormatter $dateTimeFormatter
     */
    public function __construct(
        ServiceLink $gridManagerLink,
        Translator $translator,
        NumberFormatter $numberFormatter,
        DateTimeFormatter $dateTimeFormatter
    ) {
        $this->gridManagerLink   = $gridManagerLink;
        $this->translator        = $translator;
        $this->numberFormatter   = $numberFormatter;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $gridName   = $this->context->getOption('gridName');
        $gridConfig = $this->gridManagerLink->getService()->getConfigurationForGrid($gridName);
        $columns    = $gridConfig->offsetGet('columns');

        $result = array();
        foreach ($columns as $columnName => $column) {
            $val = isset($exportedRecord[$columnName]) ? $exportedRecord[$columnName] : null;
            $val = $this->applyFrontendFormatting(
                $val,
                isset($column['frontend_type']) ? $column['frontend_type'] : null
            );
            $result[$this->translator->trans($column['label'])] = $val;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        throw new \RuntimeException('The convertToImportFormat method is not implemented.');
    }

    /**
     * @param mixed $val
     * @param string|null $frontendType
     * @return string|null
     */
    protected function applyFrontendFormatting($val, $frontendType)
    {
        if (null !== $val) {
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
            }
        }

        return $val;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}
