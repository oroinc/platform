<?php

namespace Oro\Bundle\UserBundle\Api\ApiDoc;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\UserBundle\Api\Model\UserProfile;

/**
 * Removes auto-generated API route "GET /api/userprofile/{id}".
 */
class UserProfileRestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param RestDocViewDetector $docViewDetector
     * @param ValueNormalizer     $valueNormalizer
     */
    public function __construct(RestDocViewDetector $docViewDetector, ValueNormalizer $valueNormalizer)
    {
        $this->docViewDetector = $docViewDetector;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (RestRouteOptionsResolver::ROUTE_GROUP !== $route->getOption('group')
            || ApiActions::GET !== $route->getDefault('_action')
            || $route->getDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE)
        ) {
            return;
        }

        $userProfileEntityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            UserProfile::class,
            $this->docViewDetector->getRequestType(),
            false
        );
        if (!$userProfileEntityType) {
            return;
        }

        $userProfileGetRoutePath = str_replace(
            RestRouteOptionsResolver::ENTITY_PLACEHOLDER,
            $userProfileEntityType,
            $route->getPath()
        );
        $userProfileGetRoute = $routes->getByPath($userProfileGetRoutePath, $route->getMethods());
        if (null !== $userProfileGetRoute) {
            $routes->remove($routes->getName($userProfileGetRoute));
        }
    }
}
