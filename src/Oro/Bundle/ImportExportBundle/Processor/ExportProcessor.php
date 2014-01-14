<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Symfony\Component\Serializer\SerializerInterface;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\QueryBuilderAwareInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;

class ExportProcessor implements ProcessorInterface, ContextAwareInterface
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
     * Processes entity to export format
     *
     * @param mixed $object
     * @return array
     * @throws RuntimeException
     */
    public function process($object)
    {
        if (!$this->serializer) {
            throw new RuntimeException('Serializer must be injected.');
        }
        $data = $this->serializer->serialize(
            $object,
            null,
            $this->context->getConfiguration()
        );
        if ($this->dataConverter) {
            $data = $this->dataConverter->convertToExportFormat($data);
        }
        return $data;
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    public function setImportExportContext(ContextInterface $context)
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

    /**
     * @param SerializerInterface $serializer
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
}
