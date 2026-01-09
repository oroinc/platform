<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines the contract for accessing the name prefix of a person.
 */
interface NamePrefixInterface
{
    /**
     * @return string
     */
    public function getNamePrefix();
}
