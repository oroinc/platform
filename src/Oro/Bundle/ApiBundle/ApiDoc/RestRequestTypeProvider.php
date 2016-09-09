<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RestRequestTypeProvider implements RequestTypeProviderInterface, RestDocViewDetectorAwareInterface
{
    const PLAIN_VIEW    = 'rest_plain';
    const JSON_API_VIEW = 'rest_json_api';

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /**
     * @param RestDocViewDetector $docViewDetector
     */
    public function setRestDocViewDetector(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestType()
    {
        switch ($this->docViewDetector->getView()) {
            case self::JSON_API_VIEW:
                return new RequestType([RequestType::REST, RequestType::JSON_API]);
            case self::PLAIN_VIEW:
                return new RequestType([RequestType::REST]);
        }

        return null;
    }
}
