<?php

namespace Oro\Bundle\GaufretteBundle\Exception;

use Gaufrette\Exception;

class ProtocolConfigurationException extends \RuntimeException implements Exception
{
    public function __construct()
    {
        parent::__construct(
            'The Gaufrette protocol is not configured. Make sure knp_gaufrette.stream_wrapper is configured.'
        );
    }
}
