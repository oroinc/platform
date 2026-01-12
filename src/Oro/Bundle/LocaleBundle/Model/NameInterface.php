<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines the contract for accessing the full name of a person.
 */
interface NameInterface
{
    /**
     * @return string
     */
    public function getName();
}
