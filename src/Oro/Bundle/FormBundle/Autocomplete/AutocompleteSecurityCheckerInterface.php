<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

/**
 * Interface that provides a way to check whether an access to an autocomplete search handler is granted.
 */
interface AutocompleteSecurityCheckerInterface
{
    public function getAutocompleteAclResource(string $name): ?string;
    public function isAutocompleteGranted(string $name): bool;
}
