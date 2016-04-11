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
        if (!$statusCodes->hasCode(Response::HTTP_CREATED)) {
            $statusCodes->addCode(
                Response::HTTP_CREATED,
                $this->createStatusCode('Returned when entity was successfully created')
            );
        }
        if (!$statusCodes->hasCode(Response::HTTP_BAD_REQUEST)) {
            $statusCodes->addCode(
                Response::HTTP_BAD_REQUEST,
                $this->createStatusCode('Returned when the request data is not valid')
            );
        }
        if (!$statusCodes->hasCode(Response::HTTP_FORBIDDEN)) {
            $statusCodes->addCode(
                Response::HTTP_FORBIDDEN,
                $this->createStatusCode('Returned when no permissions to create the entity')
            );
        }

        parent::addStatusCodes($statusCodes);
    }
}
