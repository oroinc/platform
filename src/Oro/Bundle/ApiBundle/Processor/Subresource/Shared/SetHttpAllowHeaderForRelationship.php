<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sets "Allow" HTTP header if the response status code is 405 (Method Not Allowed).
 * If there are no any allowed HTTP methods, the response status code is changed to 404.
 */
class SetHttpAllowHeaderForRelationship extends SetHttpAllowHeader
{
    /**
     * {@inheritdoc}
     */
    protected function getHttpMethodToActionsMap()
    {
        return [
            Request::METHOD_OPTIONS => ApiActions::OPTIONS,
            Request::METHOD_GET     => ApiActions::GET_RELATIONSHIP,
            Request::METHOD_PATCH   => ApiActions::UPDATE_RELATIONSHIP,
            Request::METHOD_POST    => ApiActions::ADD_RELATIONSHIP,
            Request::METHOD_DELETE  => ApiActions::DELETE_RELATIONSHIP
        ];
    }
}
