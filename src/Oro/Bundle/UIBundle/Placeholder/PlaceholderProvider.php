<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PlaceholderProvider
{
    const APPLICABLE = 'applicable';

    /** @var array */
    protected $placeholders;

    /** @var ResolverInterface */
    protected $resolver;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param array                         $placeholders
     * @param ResolverInterface             $resolver
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param FeatureChecker                $featureChecker
     */
    public function __construct(
        array $placeholders,
        ResolverInterface $resolver,
        AuthorizationCheckerInterface $authorizationChecker,
        FeatureChecker $featureChecker
    ) {
        $this->placeholders = $placeholders;
        $this->resolver = $resolver;
        $this->authorizationChecker = $authorizationChecker;
        $this->featureChecker = $featureChecker;
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
        if (!$this->featureChecker->isResourceEnabled($itemName, 'placeholder_items')) {
            return null;
        }
        if (isset($item['acl'])) {
            if ($this->isGranted($item['acl'])) {
                // remove 'acl' attribute as it is not needed anymore
                unset($item['acl']);
            } else {
                // the access denied for the requested item
                return null;
            }
        }
        if (array_key_exists(self::APPLICABLE, $item)) {
            if ($this->resolveApplicable($item[self::APPLICABLE], $variables)) {
                // remove 'applicable' attribute as it is not needed anymore
                unset($item[self::APPLICABLE]);
            } else {
                // the requested item is not applicable in the current context
                return null;
            }
        }

        return $this->resolver->resolve($item, $variables);
    }

    /**
     * @param array|string $conditions
     * @param array $variables
     * @return bool
     */
    protected function resolveApplicable($conditions, array $variables)
    {
        $resolved = true;
        $conditions = (array) $conditions;
        foreach ($conditions as $condition) {
            $resolved = $this->resolver->resolve(
                [
                    self::APPLICABLE => $condition
                ],
                $variables
            )[self::APPLICABLE];
            if (!$resolved) {
                break;
            }
        }

        return $resolved;
    }

    /**
     * Checks if an access to a resource is granted to the caller
     *
     * @param string|array $acl
     *
     * @return bool
     */
    protected function isGranted($acl)
    {
        if (!is_array($acl)) {
            return $this->authorizationChecker->isGranted($acl);
        }

        $result = true;
        foreach ($acl as $val) {
            if (!$this->authorizationChecker->isGranted($val)) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}
