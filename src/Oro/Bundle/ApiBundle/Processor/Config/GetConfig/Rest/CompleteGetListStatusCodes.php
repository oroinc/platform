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
        if (!$statusCodes->hasCode(Response::HTTP_OK)) {
            $statusCodes->addCode(
                Response::HTTP_OK,
                $this->createStatusCode('Returned when successful')
            );
        }
        if (!$statusCodes->hasCode(Response::HTTP_FORBIDDEN)) {
            $statusCodes->addCode(
                Response::HTTP_FORBIDDEN,
                $this->createStatusCode('Returned when no permissions to get the entities')
            );
        }

        parent::addStatusCodes($statusCodes);
    }
}
