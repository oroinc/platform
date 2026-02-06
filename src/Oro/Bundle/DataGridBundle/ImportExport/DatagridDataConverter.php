<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Converts exported records to plain format.
 * - applies formatting;
 * - sorts columns according to their "order";
 * - excludes non-renderable columns.
 */
class DatagridDataConverter implements DataConverterInterface, ContextAwareInterface, ServiceSubscriberInterface
{
    private ContextInterface $context;
    private array $gridColumns = [];

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            DatagridColumnsFromContextProviderInterface::class,
            TranslatorInterface::class,
            FormatterProvider::class
        ];
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context): void
    {
        $this->context = $context;
        // Clear grid columns cache because it is not actual for new context.
        $this->gridColumns = [];
    }

    #[\Override]
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $result = [];
        $gridColumns = $this->getGridColumns();
        foreach ($gridColumns as $columnName => $column) {
            $val = $this->applyFrontendFormatting($exportedRecord[$columnName] ?? null, $column);
            $columnLabel = isset($column['label']) ? $this->getTranslator()->trans($column['label']) : '';
            $label = $columnLabel;
            if (\array_key_exists($columnLabel, $result)) {
                $label = \sprintf('%s_%s', $columnLabel, $columnName);
            }
            $result[$label] = $val;
        }

        return $result;
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        throw new RuntimeException('The convertToImportFormat method is not implemented.');
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function applyFrontendFormatting(mixed $val, array $options): ?string
    {
        if (null !== $val) {
            $frontendType = $options['frontend_type'] ?? null;
            $formatType = $this->context->getOption(FormatterProvider::FORMAT_TYPE);
            $formatter = $frontendType && $formatType
                ? $this->getFormatterProvider()->getFormatterFor($formatType, $frontendType)
                : null;
            if ($formatter) {
                $val = $formatter->formatType($val, $frontendType);
            } else {
                switch ($frontendType) {
                    case PropertyInterface::TYPE_SELECT:
                        if (isset($options['choices'][$val])) {
                            $val = $this->getTranslator()->trans($options['choices'][$val]);
                        }
                        break;
                    case PropertyInterface::TYPE_MULTI_SELECT:
                        if (\is_array($val) && \count($val)) {
                            $val = implode(',', array_map(function ($value) use ($options) {
                                return \array_key_exists($value, $options['choices'])
                                    ? $options['choices'][$value]
                                    : '';
                            }, $val));
                        }
                        break;
                    case PropertyInterface::TYPE_HTML:
                        $val = $this->formatHtmlFrontendType($val, $options['export_type'] ?? null);
                        break;
                }
            }
        }

        return $val;
    }

    private function formatHtmlFrontendType(string $val, ?string $exportType): string
    {
        $result = trim(
            str_replace(
                "\xC2\xA0", // non-breaking space (&nbsp;)
                ' ',
                html_entity_decode(strip_tags($val), CREDITS_ALL)
            )
        );
        if ('list' === $exportType) {
            $result = preg_replace('/\s*\n\s*/', ';', $result);
        }

        return $result;
    }

    private function getGridColumns(): array
    {
        if (!$this->gridColumns) {
            $this->gridColumns = $this->getDatagridColumnsProvider()->getColumnsFromContext($this->context);
        }

        return $this->gridColumns;
    }

    private function getDatagridColumnsProvider(): DatagridColumnsFromContextProviderInterface
    {
        return $this->container->get(DatagridColumnsFromContextProviderInterface::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    private function getFormatterProvider(): FormatterProvider
    {
        return $this->container->get(FormatterProvider::class);
    }
}
