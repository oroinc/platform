<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Symfony\Component\HttpFoundation\Request;

class RequestReader extends AbstractReader
{
    /**
     * @var array
     */
    protected $data;

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if (!$context->hasOption('data')) {
            throw new InvalidConfigurationException(
                'Configuration reader must contain "data".'
            );
        } else {
            $this->data = $context->getOption('data');
        }
    }
}
