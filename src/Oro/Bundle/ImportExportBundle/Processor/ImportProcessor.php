<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

class ImportProcessor implements ProcessorInterface, ContextAwareProcessor, SerializerAwareInterface
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
     * @var DataConverterInterface
     */
    protected $dataConverter;

    /**
     * @var StrategyInterface
     */
    protected $strategy;

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
        if ($this->dataConverter) {
            $item = $this->dataConverter->convertToImportFormat($item);
        }

        $object = $this->serializer->deserialize(
            $item,
            $this->context->getOption('entityName'),
            null,
            $this->context->getConfiguration()
        );

        if ($this->strategy) {
            $object = $this->strategy->process($object);
        }

        return $object ?: null;
    }
}
