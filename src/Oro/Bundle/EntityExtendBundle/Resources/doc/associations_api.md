Creating API to Manage Associations
====================================

In this section we'll demonstrate how you can create an API for managing associations with the help of the [AssociationManager](../../Entity/Manager/AssociationManager.php).

- [Introduction to Association Manager](#introduction-to-association-manager)
- [Getting list of association targets](#getting-list-of-association-targets)
- [Utilizing the improved routing mechanism](#utilizing-the-improved-routing-mechanism)
- [Getting list of association for given targets](#getting-list-of-association-for-given-targets)

Introduction to Association Manager
------------------------------------

The [AssociationManager](../../Entity/Manager/AssociationManager.php) is designed to provide necessary functions for fetching all information that is needed for managing [associations](./associations.md) between entities.

Here is a brief description of its methods:

`getAssociationTargets()` - used to fetch the list of fields responsible for storing associations for the given entity type.
It is useful for getting `$associationTargets` or `$associationOwners` to be passed to `getMultiAssociationsQueryBuilder()` and `getMultiAssociationOwnersQueryBuilder()` methods respectively.
Also, with its help it is possible to make an API listing all association targets for a given entity or all associated entities for a given target.

`getSingleOwnerFilter()` and `getMultiOwnerFilter()` - provide functions, which can be used to filter enabled single or multi owner associations.
They are basically used to filter associations returned from `getAssociationTargets()` method to get active associations only.

`getMultiAssociationsQueryBuilder()` - provides the query builder that can be used in the API for fetching the list of targets associated with the given entity class.
This method very useful for API as it utilizes filters and joins provided by the API configuration as well as pagination and sorting data.
In case of activities, it is used to get th list of entities associated with the specified activity entity.

`getMultiAssociationOwnersQueryBuilder()` - provides the query builder that can be used in the API for fetching the list of entities associated with the given target class.
This method very useful for API as it utilizes filters and joins provided by the API configuration as well as pagination and sorting data.
In case of activities, it is used to get the list of activities associated with the specified entity.

Note: The AssociationManager utilizes capabilities of the [SqlQueryBuilder](../../../EntityBundle/ORM/SqlQueryBuilder.php) that has been introduced to handle native SQL queries.
In the AssociationManager it is used for 'UNION' operations that are not supported by Doctrine's QueryBuilder.


Getting list of association targets
------------------------------------

Let's consider an example with activities, which represent the most complicated type of associations - Multiple Many-to-many.

Imagine that we need an API method to return all association targets for the given entity type.

At first, to be able to work with associations in API we need to create the entity manager responsible for this type of associations, for example [ActivityManager](../../../ActivityBundle/Manager/ActivityManager.php).
You see that it has the AssociationManager injected into it as a dependency:
```yaml
    oro_activity.manager:
        class: %oro_activity.manager.class%
        arguments:
            ...
            - @oro_entity_extend.association_manager
            ...
```

First of all we'll need a way of getting the list of fields responsible for storing associations for the given entity. For this we can utilize the `getAssociationTargets()` method like this:
```php
    /**
     * Returns the list of fields responsible to store activity associations for the given activity entity type
     *
     * @param string $activityClassName The FQCN of the activity entity
     *
     * @return array [target_entity_class => field_name]
     */
    public function getActivityTargets($activityClassName)
    {
        return $this->associationManager->getAssociationTargets(
            $activityClassName,
            $this->associationManager->getMultiOwnerFilter('activity', 'activities'),
            RelationType::MANY_TO_MANY,
            ActivityScope::ASSOCIATION_KIND
        );
    }
```

As you can see, in the `getAssociationTargets()` method we use the `getMultiOwnerFilter()` method to filter returned associations. In our case we select only active  multi-owner associations.


After this we can create a method to return a query builder for getting the list of associated target entities that will be used in API manager later.

```php
    /**
     * Returns a query builder that could be used for fetching the list of entities
     * associated with the given activity
     *
     * @param string        $activityClassName The FQCN of the activity entity
     * @param mixed         $filters           Criteria is used to filter activity entities
     *                                         e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins             Additional associations required to filter activity entities
     * @param int|null      $limit             The maximum number of items per page
     * @param int|null      $page              The page number
     * @param string|null   $orderBy           The ordering expression for the result
     * @param callable|null $callback          A callback function which can be used to modify child queries
     *                                         function (QueryBuilder $qb, $targetEntityClass)
     *
     * @return SqlQueryBuilder|null SqlQueryBuilder object or NULL if the given entity type has no activity associations
     */
    public function getActivityTargetsQueryBuilder(
        $activityClassName,
        $filters,
        $joins = null,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $targets = $this->getActivityTargets($activityClassName);
        if (empty($targets)) {
            return null;
        }

        return $this->associationManager->getMultiAssociationsQueryBuilder(
            $activityClassName,
            $filters,
            $joins,
            $targets,
            $limit,
            $page,
            $orderBy,
            $callback
        );
    }
```

As you can see, this method is pretty simple once we have the AssociationManager in place.


For the second step we will create the API entity manager that will use our association manager (in this case ActivityManager) for getting the list of target entities:

```php
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityEntityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     */
    public function __construct(ObjectManager $om, ActivityManager $activityManager)
    {
        parent::__construct(null, $om);
        $this->activityManager = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        return $this->activityManager->getActivityTargetsQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy
        );
    }
}
```

As you can see, the API entity manager should be extended from `ApiEntityManager`. And all that's left is to override `getListQueryBuilder()` method.
Here is how we register the API entity manager in the DI container:

```yaml
    oro_activity.manager.activity_entity.api:
        class: %oro_activity.manager.activity_entity.api.class%
        parent: oro_soap.manager.entity_manager.abstract
        arguments:
            - @doctrine.orm.entity_manager
            - @oro_activity.manager
```

And finally, we'll create the API controller. For the case with activities it will look like this:

```php
/**
 * @RouteResource("activity_relation")
 * @NamePrefix("oro_api_")
 */
class ActivityEntityController extends RestController
{
    /**
     * Returns the list of entities associated with the specified activity entity.
     *
     * @param Request $request
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @Get("/activities/{activity}/{id}/relations", name="")
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     *
     * @ApiDoc(
     *      description="Returns the list of entities associated with the specified activity entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction(Request $request, $activity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(['id' => ['=', $id]]);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return ActivityEntityApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_entity.api');
    }
```

The method for getting the list of association targets does not differ much from other APIs.
The `handleGetListRequest()` method will use the `getListQueryBuilder()` method defined in our API entity manager to get the necessary associations.


Utilizing the improved routing mechanism
-----------------------------------------

As you can see the route for the API controller described above is dynamic and depends on the activity entity type.
To make it work the [Routing Component](../../../../Component/Routing) is utilized. It allows grouping and sorting of routes.

Let's see how this is implemented in case of activities.
To enable grouping for routes we need to add the `group` option in the routing definitions, e.g.:

```yaml
oro_activity_bundle_api:
    resource:     "@OroActivityBundle/Resources/config/oro/routing_api.yml"
    type:         rest
    prefix:       api/rest/{version}
    ...
    options:
        group: activity_association
```

And then register a custom Route Option Resolver using `oro.api.routing_options_resolver` tag to handle this group:
```yaml
    oro_activity.routing.options_resolver.activity_association:
        class: Oro\Bundle\ActivityBundle\Routing\ActivityAssociationRouteOptionsResolver
        public: false
        arguments:
            - @oro_entity_config.provider.grouping
            - @oro_entity.entity_alias_resolver
        tags:
            - { name: oro.api.routing_options_resolver, view: default }
```

In the route options resolver, in the `resolve()` method, we can check if the route belongs to our group and make changes to the route collection accordingly:

```php
    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if ($route->getOption('group') !== 'activity_association') {
            return;
        }
        ...
    }
```

The [RouteCollectionAccessor](../../../../Component/Routing/Resolver/RouteCollectionAccessor.php), allows standard collection manipulations with routes, such as inserting, removing, as well as appending and prepending.

You can refer to the [ActivityAssociationRouteOptionsResolver](../../../ActivityBundle/Routing/ActivityAssociationRouteOptionsResolver.php) to see the full implementation.

For more detailed information, please take a look at [Routing Component](../../../../Component/Routing) documentation.

Getting list of association for given targets
------------------------------------

Let's consider a case when we need an API method to return all associations for given target entity.

For a start, we need the entity manager to handle the associations between entities. As for the previous case, we'll use the [ActivityManager](../../../ActivityBundle/Manager/ActivityManager.php) as an example.
You can see that it has the AssociationManager injected into it as a dependency:
```yaml
    oro_activity.manager:
        class: %oro_activity.manager.class%
        arguments:
            ...
            - @oro_entity_extend.association_manager
            ...
```

Firstly, we need to retrieve the list of fields responsible for storing associations for the given target entity type.

```php
    /**
     * Returns the list of fields responsible to store activity associations for the given target entity type
     *
     * @param string $targetClassName The FQCN of the target entity
     *
     * @return array [activity_entity_class => field_name]
     */
    public function getActivities($targetClassName)
    {
        $result = [];
        foreach ($this->getActivityTypes() as $activityClass) {
            $targets = $this->getActivityTargets($activityClass);
            if (isset($targets[$targetClassName])) {
                $result[$activityClass] = $targets[$targetClassName];
            }
        }

        return $result;
    }
```

For the case with activities, it is a bit complicated since we can have different entities as activity and therefore we need to pre-fetch them using `getActivityTypes()` method. For simpler cases we'll need to call `getActivityTargets()` method only once for the given entity class.

Having the list of fields we can proceed to creating a query builder that will be used for getting the list of entities associated with the given target entity.

```php
    /**
     * Returns a query builder that could be used for fetching the list of activity entities
     * associated with the given target entity
     *
     * @param string        $targetClassName The FQCN of the activity entity
     * @param mixed         $filters         Criteria is used to filter activity entities
     *                                       e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins           Additional associations required to filter activity entities
     * @param int|null      $limit           The maximum number of items per page
     * @param int|null      $page            The page number
     * @param string|null   $orderBy         The ordering expression for the result
     * @param callable|null $callback        A callback function which can be used to modify child queries
     *                                       function (QueryBuilder $qb, $ownerEntityClass)
     *
     * @return SqlQueryBuilder|null SqlQueryBuilder object or NULL if the given entity type has no activity associations
     */
    public function getActivitiesQueryBuilder(
        $targetClassName,
        $filters,
        $joins = null,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $activities = $this->getActivities($targetClassName);
        if (empty($activities)) {
            return null;
        }

        return $this->associationManager->getMultiAssociationOwnersQueryBuilder(
            $targetClassName,
            $filters,
            $joins,
            $activities,
            $limit,
            $page,
            $orderBy,
            $callback
        );
    }
```

For the next step we will create the API entity manager that will use our association manager (in this case ActivityManager) for getting the list of entities associated to the given target entity:

```php
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityTargetApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     */
    public function __construct(ObjectManager $om, ActivityManager $activityManager)
    {
        parent::__construct(null, $om);
        $this->activityManager = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        return $this->activityManager->getActivitiesQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy
        );
    }
}
```

And register it in the DI container:

```yaml
    oro_activity.manager.activity_target.api:
        class: %oro_activity.manager.activity_target.api.class%
        parent: oro_soap.manager.entity_manager.abstract
        arguments:
            - @doctrine.orm.entity_manager
            - @oro_activity.manager
```

Note that the API entity manager is extended from `ApiEntityManager` and overrides `getListQueryBuilder()` method.

As the last step, we create the API controller. For the case with activities it will look like this:

```php
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityTargetApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

/**
 * @RouteResource("activity_target")
 * @NamePrefix("oro_api_")
 */
class ActivityTargetController extends RestGetController
{

    /**
     * Returns the list of activities associated with the specified entity.
     *
     * @param Request $request
     * @param string $entity The type of the target entity.
     * @param mixed  $id     The id of the target entity.
     *
     * @Get("/activities/targets/{entity}/{id}", name="")
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     *
     * @ApiDoc(
     *      description="Returns the list of activities associated with the specified entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getActivitiesAction(Request $request, $entity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(['id' => ['=', $id]], [], ['id' => 'target.id']);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return ActivityTargetApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_target.api');
    }
}
```

The action for getting the list of association targets is very similar to other APIs. The main trick is to build the filter criteria correctly that will be passed to the `getListQueryBuilder()` method in the API entity manager.
