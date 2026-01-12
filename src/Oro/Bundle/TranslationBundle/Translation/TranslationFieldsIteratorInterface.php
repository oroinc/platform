<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Defines the contract for iterating over translatable fields.
 *
 * Extends {@see IteratorAggregate} to provide iteration capabilities over translatable fields
 * in an entity. Allows writing values to the current field position during iteration,
 * enabling bulk operations on translatable entity fields.
 */
interface TranslationFieldsIteratorInterface extends \IteratorAggregate
{
    /**
     * Writes value to the field under current position of iterator
     *
     * @param mixed $value
     * @return void
     */
    public function writeCurrent($value);
}
