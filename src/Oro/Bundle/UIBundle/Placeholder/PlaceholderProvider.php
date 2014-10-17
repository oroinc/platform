<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Config\Resolver\ResolverInterface;

class PlaceholderProvider
{
    /** @var array */
    protected $placeholders;

    /** @var ResolverInterface */
    protected $resolver;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param array             $placeholders
     * @param ResolverInterface $resolver
     * @param SecurityFacade    $securityFacade
     */
    public function __construct(array $placeholders, ResolverInterface $resolver, SecurityFacade $securityFacade)
    {
        $this->placeholders   = $placeholders;
        $this->resolver       = $resolver;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Gets items by placeholder name
     *
     * @param string $placeholderName
     * @param array  $variables
     *
     * @return array
     */
    public function getPlaceholderItems($placeholderName, array $variables)
    {
        $result = [];

        if (!isset($this->placeholders['placeholders'][$placeholderName])) {
            return $result;
        }

        foreach ($this->placeholders['placeholders'][$placeholderName]['items'] as $itemName) {
            $item = $this->getItem($itemName, $variables);
            if (!empty($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Gets item by name
     *
     * @param string $itemName
     * @param array  $variables
     *
     * @return array|null
     */
    public function getItem($itemName, array $variables)
    {
        if (!isset($this->placeholders['items'][$itemName])) {
            // the requested item does not exist
            return null;
        }

        $item = $this->placeholders['items'][$itemName];
        if (isset($item['acl'])) {
            if ($this->isGranted($item['acl'])) {
                // remove 'acl' attribute as it is not needed anymore
                unset($item['acl']);
            } else {
                // the access denied for the requested item
                return null;
            }
        }
        if (isset($item['applicable'])) {
            $resolved = $this->resolver->resolve(['applicable' => $item['applicable']], $variables);
            if ($resolved['applicable'] === true) {
                // remove 'applicable' attribute as it is not needed anymore
                unset($item['applicable']);
            } else {
                // the requested item is not applicable in the current context
                return null;
            }
        }

        return $this->resolver->resolve($item, $variables);
    }

    /**
     * Checks if an access to a resource is granted to the caller
     *
     * This method supports a single ACL identifier or a list of ACL identifier delimited by ' && '.
     * In the last case an access is granted only when it is granted for all ACL identifiers.
     * TODO: in the future Symfony Expression Language should be used here
     *
     * @param string $aclExpr
     *
     * @return bool
     */
    protected function isGranted($aclExpr)
    {
        if (empty($aclExpr)) {
            return true;
        }

        $items = explode(' && ', $aclExpr);
        if (count($items) === 1) {
            return $this->securityFacade->isGranted($aclExpr);
        }

        $result = true;
        foreach ($items as $item) {
            if (!$this->securityFacade->isGranted($item)) {
                $result = false;
                break;
            }
        }
        
        return $result;
    }
}
