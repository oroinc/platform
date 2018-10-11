<?php

namespace Oro\Bundle\ApiBundle\Processor\Options;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * The execution context for processors for "options" action.
 */
class OptionsContext extends SubresourceContext
{
    public const ACTION_TYPE_ITEM         = 'item';
    public const ACTION_TYPE_LIST         = 'list';
    public const ACTION_TYPE_SUBRESOURCE  = 'subresource';
    public const ACTION_TYPE_RELATIONSHIP = 'relationship';

    /** the type of action, can be "item", "list", "subresource" or "relationship" */
    private const ACTION_TYPE = 'actionType';

    /** allowed action types */
    private const ACTION_TYPES = [
        self::ACTION_TYPE_ITEM,
        self::ACTION_TYPE_LIST,
        self::ACTION_TYPE_SUBRESOURCE,
        self::ACTION_TYPE_RELATIONSHIP
    ];

    /** an identifier of an entity */
    private const ID = 'id';

    /**
     * Gets the type of action.
     *
     * @return string Can be "item", "list", "subresource" or "relationship"
     *
     * @throws \BadMethodCallException if the action type is not set
     */
    public function getActionType(): string
    {
        $actionType = $this->get(self::ACTION_TYPE);
        if (!$actionType) {
            throw new \BadMethodCallException('The action type is not set yet.');
        }

        return $actionType;
    }

    /**
     * Sets the type of action.
     *
     * @param string $actionType Can be "item", "list", "subresource" or "relationship"
     *
     * @throws \InvalidArgumentException if the action type is not valid
     */
    public function setActionType(string $actionType): void
    {
        if (!\in_array($actionType, self::ACTION_TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The action type must be one of %s. Given: %s.',
                \implode(', ', self::ACTION_TYPES),
                $actionType
            ));
        }
        $this->set(self::ACTION_TYPE, $actionType);
    }

    /**
     * Gets an identifier of an entity.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->get(self::ID);
    }

    /**
     * Sets an identifier of an entity.
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->set(self::ID, $id);
    }
}
