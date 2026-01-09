<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Provides common functionality for Behat entity page objects.
 *
 * This base class extends {@see Element} to represent entity view/edit pages in Behat tests.
 * Subclasses must implement the `assertPageContainsValue` method to verify that entity field values
 * are correctly displayed on the page.
 */
abstract class EntityPage extends Element
{
    /**
     * @param string $label
     * @param string $value
     */
    abstract public function assertPageContainsValue($label, $value);
}
