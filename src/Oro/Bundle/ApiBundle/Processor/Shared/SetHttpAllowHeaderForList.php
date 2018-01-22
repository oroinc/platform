<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Sets "Allow" HTTP header if the response status code is 405 (Method Not Allowed).
 * In case if there are no any allowed HTTP methods, the response status code is changed to 404.
 */
class SetHttpAllowHeaderForList extends SetHttpAllowHeader
{
    /**
     * {@inheritdoc}
     */
    protected function getHttpMethodToActionsMap()
    {
        return [
            self::METHOD_GET    => ApiActions::GET_LIST,
            self::METHOD_DELETE => ApiActions::DELETE_LIST
        ];
    }
}
