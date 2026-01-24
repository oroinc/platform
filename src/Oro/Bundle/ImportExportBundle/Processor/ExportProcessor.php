<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\QueryBuilderAwareInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;

/**
 * Processes entities for export by normalizing and converting them to export format.
 *
 * This processor coordinates the export pipeline: it normalizes entities using the serializer,
 * then optionally converts the normalized data to the final export format using a data converter.
 * It supports context-aware and query builder-aware data converters for advanced export scenarios.
 */
class ExportProcessor implements ContextAwareProcessor, EntityNameAwareProcessor
{
    protected ?ContextInterface $context = null;

    protected ?SerializerInterface $serializer = null;

    /**
     * @var DataConverterInterface|EntityNameAwareInterface|ContextAwareInterface|QueryBuilderAwareInterface|null
     */
    protected ?DataConverterInterface $dataConverter = null;

    protected string $entityName = '';

    /**
     * Processes entity to export format
     *
     *
     * @throws RuntimeException
     */
    #[\Override]
    public function process($item)
    {
        if (!$this->serializer) {
            throw new RuntimeException('Serializer must be injected.');
        }

        $format = '';
        $context = $this->context->getConfiguration();

        $data = $this->serializer->normalize($item, $format, $context);

        if ($this->dataConverter) {
            $data = $this->dataConverter->convertToExportFormat($data);
        }

        return $data;
    }

    /**
     * @throws InvalidConfigurationException
     */
    #[\Override]
    public function setImportExportContext(ContextInterface $context): void
    {
        $this->context = $context;

        $queryBuilder = $context->getOption('queryBuilder');
        if (isset($queryBuilder) && $this->dataConverter instanceof QueryBuilderAwareInterface) {
            if (!$queryBuilder instanceof QueryBuilder) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'Configuration of processor contains invalid "queryBuilder" option. '
                        . '"Doctrine\ORM\QueryBuilder" type is expected, but "%s" is given',
                        is_object($queryBuilder) ? get_class($queryBuilder) : gettype($queryBuilder)
                    )
                );
            }
            $this->dataConverter->setQueryBuilder($queryBuilder);
        }

        if ($this->dataConverter && $this->dataConverter instanceof ContextAwareInterface) {
            $this->dataConverter->setImportExportContext($context);
        }
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function setDataConverter(DataConverterInterface $dataConverter): void
    {
        $this->dataConverter = $dataConverter;
    }

    #[\Override]
    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;

        if ($this->dataConverter && $this->dataConverter instanceof EntityNameAwareInterface) {
            $this->dataConverter->setEntityName($this->entityName);
        }
    }
}
