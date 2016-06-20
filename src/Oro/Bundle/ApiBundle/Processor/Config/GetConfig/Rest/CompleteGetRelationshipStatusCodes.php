<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * Adds possible status codes for the "get_relationship" action executed in scope of REST API.
 */
class CompleteGetRelationshipStatusCodes extends CompleteStatusCodes
{
    /**
     * {@inheritdoc}
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to get the entities'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when when the parent entity of the relationship does not exist'
        );

        parent::addStatusCodes($statusCodes);
    }
}
