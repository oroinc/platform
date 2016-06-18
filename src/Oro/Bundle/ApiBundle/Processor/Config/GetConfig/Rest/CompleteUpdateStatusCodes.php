<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * Adds possible status codes for the "update" action executed in scope of REST API.
 */
class CompleteUpdateStatusCodes extends CompleteStatusCodes
{
    /**
     * {@inheritdoc}
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when entity was successfully updated'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_BAD_REQUEST,
            'Returned when the request data is not valid'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the entity is not found'
        );

        parent::addStatusCodes($statusCodes);
    }
}
