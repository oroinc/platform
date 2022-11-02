<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The provider for placeholders configuration.
 */
class PlaceholderProvider
{
    private const APPLICABLE    = 'applicable';
    private const ACL           = 'acl';
    private const RESOURCE_TYPE = 'placeholder_items';

    /** @var PlaceholderConfigurationProvider */
    private $configProvider;

    /** @var ResolverInterface */
    private $resolver;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var FeatureChecker */
    private $featureChecker;

    public function __construct(
        PlaceholderConfigurationProvider $configProvider,
        ResolverInterface $resolver,
        AuthorizationCheckerInterface $authorizationChecker,
        FeatureChecker $featureChecker
    ) {
        $this->configProvider = $configProvider;
        $this->resolver = $resolver;
        $this->authorizationChecker = $authorizationChecker;
        $this->featureChecker = $featureChecker;
    }

    /**
     * Gets items by placeholder name.
     *
     * @param string $placeholderName
     * @param array  $variables
     *
     * @return array
     */
    public function getPlaceholderItems(string $placeholderName, array $variables): ?array
    {
        $result = [];

        $placeholderItems = $this->configProvider->getPlaceholderItems($placeholderName);
        if ($placeholderItems) {
            foreach ($placeholderItems as $itemName) {
                $item = $this->getItem($itemName, $variables);
                if (!empty($item)) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * Gets item by name.
     */
    public function getItem(string $itemName, array $variables): ?array
    {
        $item = $this->configProvider->getItemConfiguration($itemName);
        if (!$item) {
            return null;
        }

        if (!$this->featureChecker->isResourceEnabled($itemName, self::RESOURCE_TYPE)) {
            return null;
        }

        if (isset($item[self::ACL])) {
            if (!$this->isGranted($item[self::ACL])) {
                return null;
            }

            // remove 'acl' attribute as it is not needed anymore
            unset($item[self::ACL]);
        }

        if (\array_key_exists(self::APPLICABLE, $item)) {
            if (!$this->isApplicable($item[self::APPLICABLE], $variables)) {
                return null;
            }

            // remove 'applicable' attribute as it is not needed anymore
            unset($item[self::APPLICABLE]);
        }

        return $this->resolver->resolve($item, $variables);
    }

    /**
     * @param string[]|string $conditions
     * @param array           $variables
     *
     * @return bool
     */
    private function isApplicable($conditions, array $variables): bool
    {
        $resolved = true;
        $conditions = (array)$conditions;
        foreach ($conditions as $condition) {
            $value = [self::APPLICABLE => $condition];
            $value = $this->resolver->resolve($value, $variables);
            $resolved = $value[self::APPLICABLE];
            if (!$resolved) {
                break;
            }
        }

        return $resolved;
    }

    /**
     * @param string[]|string $acl
     *
     * @return bool
     */
    private function isGranted($acl): bool
    {
        if (!\is_array($acl)) {
            return $this->authorizationChecker->isGranted($acl);
        }

        foreach ($acl as $val) {
            if (!$this->authorizationChecker->isGranted($val)) {
                return false;
            }
        }

        return true;
    }
}
