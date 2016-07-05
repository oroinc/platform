<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * All the supported Data API actions which are implemented "out of the box".
 */
class ApiActions
{
    const GET                 = 'get';
    const GET_LIST            = 'get_list';
    const UPDATE              = 'update';
    const CREATE              = 'create';
    const DELETE              = 'delete';
    const DELETE_LIST         = 'delete_list';
    const GET_SUBRESOURCE     = 'get_subresource';
    const GET_RELATIONSHIP    = 'get_relationship';
    const UPDATE_RELATIONSHIP = 'update_relationship';
    const ADD_RELATIONSHIP    = 'add_relationship';
    const DELETE_RELATIONSHIP = 'delete_relationship';
}
