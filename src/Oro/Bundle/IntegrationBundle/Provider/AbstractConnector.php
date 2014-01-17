<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;

abstract class AbstractConnector extends AbstractReader implements ConnectorInterface
{
    const ENTITY_NAME     = null;
    const CONNECTOR_LABEL = null;

    const JOB_VALIDATE_IMPORT = null;
    const JOB_IMPORT          = null;

    /** @var TransportInterface */
    protected $transport;

    /** @var ParameterBag */
    protected $transportSettings;

    /** @var LoggerStrategy */
    protected $logger;

    /**
     * @param ContextRegistry $contextRegistry
     * @param LoggerStrategy  $logger
     */
    public function __construct(ContextRegistry $contextRegistry, LoggerStrategy $logger)
    {
        parent::__construct($contextRegistry);
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        // read peace of data, skipping empty
        do {
            $data = $this->doRead();
        } while ($data === false);

        if (is_null($data)) {
            return null; // no data anymore
        }

        $context = $this->getContext();
        $context->incrementReadCount();
        $context->incrementReadOffset();

        // connectors should know how to advance
        // batch counter/boundaries to the next ones
        return $data;
    }

    /**
     * Should be overridden in descendant classes
     *
     * Should return
     *     null in case when no more data to read
     *     false if just current batch is empty
     *     data if read
     *
     * @return mixed
     */
    abstract protected function doRead();

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->transport         = $context->getOption('transport');
        $this->transportSettings = $context->getOption('transportSettings');

        if (!$this->transportSettings || !$this->transport) {
            throw new \LogicException('Connector instance does not configured properly.');
        }

        $this->transport->init($this->transportSettings);
    }

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    public function getImportEntityFQCN()
    {
        return static::ENTITY_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return static::CONNECTOR_LABEL;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName($isValidationOnly = false)
    {
        if ($isValidationOnly) {
            return static::JOB_VALIDATE_IMPORT;
        }

        return static::JOB_IMPORT;
    }
}
