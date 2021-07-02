<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * Represents an execution context for API processors for actions that is used to change data, such as:
 * "create", "update", "delete", "delete_list",
 * "update_relationship", "add_relationship", "delete_relationship",
 * "update_subresource", "add_subresource", "delete_subresource",
 * "customize_form_data".
 */
interface ChangeContextInterface extends SharedDataAwareContextInterface
{
    /**
     * Gets all entities, primary and included ones, that are processing by an action.
     *
     * @param bool $mainOnly Whether only main entity(ies) for this request
     *                       or all, primary and included entities should be returned
     *
     * @return object[]
     */
    public function getAllEntities(bool $mainOnly = false): array;
}
