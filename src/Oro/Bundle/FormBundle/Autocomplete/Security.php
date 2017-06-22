<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Security
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var array */
    protected $autocompleteAclResources;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->autocompleteAclResources = [];
    }

    /**
     * @param string $name
     * @param string $aclResource
     */
    public function setAutocompleteAclResource($name, $aclResource)
    {
        $this->autocompleteAclResources[$name] = $aclResource;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getAutocompleteAclResource($name)
    {
        return isset($this->autocompleteAclResources[$name]) ? $this->autocompleteAclResources[$name] : null;
    }

    /**
     * @param $name
     * @return boolean
     */
    public function isAutocompleteGranted($name)
    {
        $aclResource = $this->getAutocompleteAclResource($name);

        if ($aclResource) {
            return $this->authorizationChecker->isGranted($aclResource);
        }

        return true;
    }
}
