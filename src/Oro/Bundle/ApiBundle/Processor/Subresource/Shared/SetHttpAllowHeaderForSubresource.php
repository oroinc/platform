<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sets "Allow" HTTP header if the response status code is 405 (Method Not Allowed).
 * If there are no any allowed HTTP methods, the response status code is changed to 404.
 */
class SetHttpAllowHeaderForSubresource extends SetHttpAllowHeader
{
    /**
     * {@inheritdoc}
     */
    protected function getHttpMethodToActionsMap(): array
    {
        return [
            Request::METHOD_OPTIONS => ApiAction::OPTIONS,
            Request::METHOD_GET     => ApiAction::GET_SUBRESOURCE,
            Request::METHOD_PATCH   => ApiAction::UPDATE_SUBRESOURCE,
            Request::METHOD_POST    => ApiAction::ADD_SUBRESOURCE,
            Request::METHOD_DELETE  => ApiAction::DELETE_SUBRESOURCE
        ];
    }
}
