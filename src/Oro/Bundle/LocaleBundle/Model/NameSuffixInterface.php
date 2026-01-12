<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines the contract for accessing the name suffix of a person.
 */
interface NameSuffixInterface
{
    /**
     * @return string
     */
    public function getNameSuffix();
}
