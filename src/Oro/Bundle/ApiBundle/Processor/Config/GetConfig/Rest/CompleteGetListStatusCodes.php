<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * Adds possible status codes for the "get_list" action executed in scope of REST API.
 */
class CompleteGetListStatusCodes extends CompleteStatusCodes
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

        parent::addStatusCodes($statusCodes);
    }
}
