<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

/**
 * Processes given data by applying a data converter, serializer and an import strategy.
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        $this->context->setValue('rawItemData', $item);

        if ($this->dataConverter) {
            $item = $this->dataConverter->convertToImportFormat($item, false);
        }

        $this->context->setValue('itemData', $item);

        $object = $this->serializer->denormalize($item, $this->getEntityName(), '', $this->context->getConfiguration());

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
}
