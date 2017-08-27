<?php

namespace Oro\Bundle\UserBundle\Api\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\UserBundle\Api\Model\UserProfile;

/**
 * Adds the route "GET /api/userprofile". The name of this route is "oro_rest_api_get_user_profile".
 */
class UserProfileRestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const GET_ROUTE_NAME          = 'oro_rest_api_get';
    const USER_PROFILE_ROUTE_NAME = 'oro_rest_api_get_user_profile';

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (RestRouteOptionsResolver::ROUTE_GROUP !== $route->getOption('group')
            || ApiActions::GET !== $route->getDefault('_action')
            || self::GET_ROUTE_NAME !== $routes->getName($route)
        ) {
            return;
        }

        $userProfileEntityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            UserProfile::class,
            new RequestType([RequestType::REST]),
            false
        );
        if (!$userProfileEntityType) {
            return;
        }

        $userProfileGetRoute = $routes->cloneRoute($route);
        // remove "{id}" placeholder and replace "{entity}" placeholder with
        // the alias of the user profile model
        $userProfileGetRoute->setPath(
            str_replace(
                RestRouteOptionsResolver::ENTITY_PLACEHOLDER,
                $userProfileEntityType,
                str_replace('/{id}', '', $userProfileGetRoute->getPath())
            )
        );
        // set "entity" attribute and remove it from the requirements
        $userProfileGetRoute->setDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE, $userProfileEntityType);
        $requirements = $userProfileGetRoute->getRequirements();
        unset($requirements[RestRouteOptionsResolver::ENTITY_ATTRIBUTE]);
        $userProfileGetRoute->setRequirements($requirements);
        // add the user profile route before the default "get" route
        $routes->insert(
            self::USER_PROFILE_ROUTE_NAME,
            $userProfileGetRoute,
            self::GET_ROUTE_NAME,
            true
        );
    }
}
