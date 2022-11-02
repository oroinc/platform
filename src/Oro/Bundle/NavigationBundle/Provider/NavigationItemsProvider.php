<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationRepositoryInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Provides navigation items.
 */
class NavigationItemsProvider implements NavigationItemsProviderInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ItemFactory */
    private $itemFactory;

    /** @var UrlMatcherInterface */
    private $urlMatcher;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ItemFactory $itemFactory,
        UrlMatcherInterface $urlMatcher
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->itemFactory = $itemFactory;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(
        UserInterface $user,
        Organization $organization,
        string $type,
        array $options = []
    ): array {
        /** @var $entity \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
        $entity = $this->itemFactory->createItem($type, []);

        /** @var $repo NavigationRepositoryInterface */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(ClassUtils::getClass($entity));

        $items = $repo->getNavigationItems($user, $organization, $type, $options);

        return \array_values(array_filter($items, [$this, 'isItemEnabled']));
    }

    private function isItemEnabled(array $item): bool
    {
        $route = $this->getMatchedRoute($item);

        return $route && $this->featureChecker->isResourceEnabled($route, 'routes');
    }

    /**
     * Matches a route and return its name.
     */
    private function getMatchedRoute(array $item): ?string
    {
        if (isset($item['route'])) {
            return $item['route'];
        }

        try {
            $path = parse_url($item['url'], PHP_URL_PATH);
            if ($path === null) {
                $path = '';
            }

            $routeMatch = $this->urlMatcher->match($path);

            return $routeMatch['_route'] ?? null;
        } catch (ResourceNotFoundException $e) {
            // Keeps silence, it is ok that resource was not found.
            return null;
        } catch (MethodNotAllowedException $e) {
            // Keeps silence, it is ok that resource was not found.
            return null;
        }
    }
}
