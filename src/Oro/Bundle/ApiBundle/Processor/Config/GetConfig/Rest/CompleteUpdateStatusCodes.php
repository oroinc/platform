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
        if (!$statusCodes->hasCode(Response::HTTP_OK)) {
            $statusCodes->addCode(
                Response::HTTP_OK,
                $this->createStatusCode('Returned when entity was successfully updated')
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
                $this->createStatusCode('Returned when no permissions to update the entity')
            );
        }
        if (!$statusCodes->hasCode(Response::HTTP_NOT_FOUND)) {
            $statusCodes->addCode(
                Response::HTTP_NOT_FOUND,
                $this->createStatusCode('Returned when the entity is not found')
            );
        }

        parent::addStatusCodes($statusCodes);
    }
}
