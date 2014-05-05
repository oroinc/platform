<?php

namespace Oro\Bundle\IntegrationBundle\Processor;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

class ReverseProcessor implements ProcessorInterface, ContextAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @param mixed $object
     *
     * @return mixed
     */
    public function process($object)
    {
        return $object;
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}
