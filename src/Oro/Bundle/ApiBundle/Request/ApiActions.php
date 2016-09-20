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

    /**
     * Returns true in case if action supports input data.
     *
     * @param string $action
     *
     * @return bool
     */
    public static function isInputAction($action)
    {
        return in_array($action, [self::CREATE, self::UPDATE, self::UPDATE_RELATIONSHIP, self::ADD_RELATIONSHIP], true);
    }

    /**
     * Returns true in case if action returns object data.
     *
     * @param string $action
     *
     * @return bool
     */
    public static function isOutputAction($action)
    {
        return !in_array(
            $action,
            [self::DELETE, self::DELETE_LIST, self::DELETE_RELATIONSHIP],
            true
        );
    }

    /**
     * Returns true in case if input or output data can have identificator fields.
     *
     * @param $action
     *
     * @return bool
     */
    public static function isIdentificatorNeededForAction($action)
    {
        return $action !== self::CREATE;
    }

    /**
     * Returns output action whose format of data will be returned as result for the given action
     *
     * @param string $action
     *
     * @return string
     */
    public static function getActionOutputFormatActionType($action)
    {
        return in_array($action, [self::CREATE, self::UPDATE], true)
            ? self::GET
            : $action;
    }
}
