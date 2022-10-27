<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * The base class for query designer objects.
 */
abstract class AbstractQueryDesigner
{
    /**
     * Gets the FQCN of an entity on which this query designer object is based.
     *
     * @return string|null
     */
    abstract public function getEntity();

    /**
     * Sets the FQCN of an entity on which this query designer object is based.
     *
     * @param string|null $entity
     */
    abstract public function setEntity($entity);

    /**
     * Gets the JSON representation of this query designer object definition.
     *
     * Use {@see \Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil::decodeDefinition}
     * to convert the JSON representation to an array.
     *
     * @return string|null
     */
    abstract public function getDefinition();

    /**
     * Sets the JSON representation of this query designer object definition.
     *
     * Use {@see \Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil::encodeDefinition}
     * to convert an array to the JSON representation.
     *
     * @param string|null $definition
     */
    abstract public function setDefinition($definition);
}
