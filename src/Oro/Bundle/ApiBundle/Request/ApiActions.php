<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides the list of all Data API actions that are implemented "out of the box".
 */
final class ApiActions
{
    const OPTIONS             = 'options';
    const GET                 = 'get';
    const GET_LIST            = 'get_list';
    const UPDATE              = 'update';
    const CREATE              = 'create';
    const DELETE              = 'delete';
    const DELETE_LIST         = 'delete_list';
    const GET_SUBRESOURCE     = 'get_subresource';
    const UPDATE_SUBRESOURCE  = 'update_subresource';
    const ADD_SUBRESOURCE     = 'add_subresource';
    const DELETE_SUBRESOURCE  = 'delete_subresource';
    const GET_RELATIONSHIP    = 'get_relationship';
    const UPDATE_RELATIONSHIP = 'update_relationship';
    const ADD_RELATIONSHIP    = 'add_relationship';
    const DELETE_RELATIONSHIP = 'delete_relationship';
}
