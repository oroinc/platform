<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines the contract for accessing all name parts of a person.
 *
 * This interface combines all individual name part interfaces (prefix, first, middle, last, suffix)
 * to provide a unified contract for entities that support complete name information.
 */
interface FullNameInterface extends
    NamePrefixInterface,
    FirstNameInterface,
    MiddleNameInterface,
    LastNameInterface,
    NameSuffixInterface
{
}
