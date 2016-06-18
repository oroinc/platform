<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * Adds possible status codes for the "create" action executed in scope of REST API.
 */
class CompleteCreateStatusCodes extends CompleteStatusCodes
{
    /**
     * {@inheritdoc}
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_CREATED,
            'Returned when entity was successfully created'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_BAD_REQUEST,
            'Returned when the request data is not valid'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to create the entity'
        );

        parent::addStatusCodes($statusCodes);
    }
}
