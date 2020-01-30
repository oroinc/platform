<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a way to check whether an access to an autocomplete search handler is granted.
 */
class Security
{
    /** @var string[] [autocomplete search handler id => acl resource, ...] */
    private $autocompleteAclResources;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param array                         $autocompleteAclResources
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(array $autocompleteAclResources, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->autocompleteAclResources = $autocompleteAclResources;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getAutocompleteAclResource(string $name): ?string
    {
        return $this->autocompleteAclResources[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isAutocompleteGranted(string $name): bool
    {
        $aclResource = $this->getAutocompleteAclResource($name);
        if (!$aclResource) {
            return true;
        }

        return $this->authorizationChecker->isGranted($aclResource);
    }
}
