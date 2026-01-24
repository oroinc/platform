<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\ImportExportBundle\Context\BatchContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidFieldTypeException;
use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

/**
 * Processes import data by converting, denormalizing, and applying import strategies.
 *
 * This processor coordinates the import pipeline: it converts raw data to import format using a data converter,
 * denormalizes it to entity objects using the serializer, then applies an import strategy for validation, merging,
 * and persistence logic. It handles errors and tracks row numbers for detailed error reporting.
 */
class ImportProcessor implements ContextAwareProcessor, EntityNameAwareInterface
{
    protected ?ContextInterface $context = null;

    protected ?SerializerInterface $serializer = null;

    /** @var DataConverterInterface|EntityNameAwareInterface|ContextAwareInterface|null */
    protected ?DataConverterInterface $dataConverter = null;

    /** @var StrategyInterface|EntityNameAwareInterface|ContextAwareInterface|null */
    protected ?StrategyInterface $strategy = null;

    protected string $entityName = '';

    #[\Override]
    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;

        if ($this->dataConverter && $this->dataConverter instanceof EntityNameAwareInterface) {
            $this->dataConverter->setEntityName($this->entityName);
        }

        if ($this->strategy && $this->strategy instanceof EntityNameAwareInterface) {
            $this->strategy->setEntityName($this->entityName);
        }
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context): void
    {
        $this->context = $context;

        if ($this->strategy && $this->strategy instanceof ContextAwareInterface) {
            $this->strategy->setImportExportContext($this->context);
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

    public function setStrategy(StrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }

    #[\Override]
    public function process($item)
    {
        $this->context->setValue('rawItemData', $item);

        if ($this->dataConverter) {
            $item = $this->dataConverter->convertToImportFormat($item, false);
        }

        $this->context->setValue('itemData', $item);

        try {
            $object = $this->serializer->denormalize(
                $item,
                $this->getEntityName(),
                '',
                $this->context->getConfiguration()
            );
        } catch (InvalidFieldTypeException $e) {
            $this->context->addError(sprintf('Error in Row #%s. %s', $this->getCurrentRowNumber(), $e->getMessage()));
            $this->context->incrementErrorEntriesCount();

            return null;
        }

        if ($this->strategy) {
            $object = $this->strategy->process($object);
        }

        return $object ?: null;
    }

    protected function getEntityName(): string
    {
        if ($this->entityName) {
            return $this->entityName;
        }

        return $this->context->getOption('entityName');
    }

    private function getCurrentRowNumber(): int
    {
        $rowNumber = intval($this->context->getReadOffset());
        if ($this->context instanceof BatchContextInterface) {
            $rowNumber += (intval($this->context->getBatchNumber()) - 1) * intval($this->context->getBatchSize());
        }

        return $rowNumber;
    }
}
