<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ImportProcessor implements ContextAwareProcessor, SerializerAwareInterface, EntityNameAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var DataConverterInterface|EntityNameAwareInterface|ContextAwareInterface
     */
    protected $dataConverter;

    /**
     * @var StrategyInterface|EntityNameAwareInterface|ContextAwareInterface
     */
    protected $strategy;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;

        if ($this->strategy && $this->strategy instanceof ContextAwareInterface) {
            $this->strategy->setImportExportContext($this->context);
        }

        if ($this->dataConverter && $this->dataConverter instanceof ContextAwareInterface) {
            $this->dataConverter->setImportExportContext($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param DataConverterInterface $dataConverter
     */
    public function setDataConverter(DataConverterInterface $dataConverter)
    {
        $this->dataConverter = $dataConverter;
    }

    /**
     * @param StrategyInterface $strategy
     */
    public function setStrategy(StrategyInterface $strategy)
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

        $object = $this->serializer->deserialize(
            $item,
            $this->getEntityName(),
            null,
            $this->context->getConfiguration()
        );

        if ($this->strategy) {
            $object = $this->strategy->process($object);
        }

        return $object ?: null;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        if ($this->entityName) {
            return $this->entityName;
        } else {
            return $this->context->getOption('entityName');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        if ($this->dataConverter && $this->dataConverter instanceof EntityNameAwareInterface) {
            $this->dataConverter->setEntityName($this->entityName);
        }

        if ($this->strategy && $this->strategy instanceof EntityNameAwareInterface) {
            $this->strategy->setEntityName($this->entityName);
        }
    }
}
