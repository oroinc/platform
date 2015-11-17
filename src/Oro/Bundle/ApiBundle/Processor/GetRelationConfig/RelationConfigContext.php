<?php

namespace Oro\Bundle\ApiBundle\Processor\GetRelationConfig;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class RelationConfigContext extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** the name of a field */
    const FIELD_NAME = 'field';

    /** the request action, for example "get", "get_list", etc. */
    const REQUEST_ACTION = 'requestAction';

    /**
     * Gets FQCN of an entity.
     *
     * @return string|null
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Gets the name of a field.
     *
     * @return string|null
     */
    public function getFieldName()
    {
        return $this->get(self::FIELD_NAME);
    }

    /**
     * Sets the name of a field.
     *
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->set(self::FIELD_NAME, $fieldName);
    }

    /**
     * Gets the request action, for example "get", "get_list", etc.
     *
     * @return string
     */
    public function getRequestAction()
    {
        return $this->get(self::REQUEST_ACTION);
    }

    /**
     * Sets the request action, for example "get", "get_list", etc.
     *
     * @param string $requestAction
     */
    public function setRequestAction($requestAction)
    {
        $this->set(self::REQUEST_ACTION, $requestAction);
    }
}
