<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines the contract for accessing the last name of a person.
 */
interface LastNameInterface
{
    /**
     * @return string
     */
    public function getLastName();
}
