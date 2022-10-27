<?php

namespace Oro\Bundle\ActivityBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Provider\MultiGridProvider;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\UIBundle\Provider\ChainWidgetProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Serves activity actions.
 * @Route("/activities")
 */
class ActivityController extends AbstractController
{
    /**
     * @param object $entity The entity object which activities should be rendered
     *
     * @return Response
     *
     * @Route(
     *      "/view/{entity}",
     *      name="oro_activity_view_activities"
     * )
     */
    public function activitiesAction($entity)
    {
        $widgetProvider = $this->get(ChainWidgetProvider::class);

        $widgets = $widgetProvider->supports($entity)
            ? $widgetProvider->getWidgets($entity)
            : [];

        if (empty($widgets)) {
            // return empty response to prevent rendering 'Activities' placeholder
            return new Response();
        }

        return $this->render('@OroActivity/Activity/activities.html.twig', ['tabs' => $widgets]);
    }

    /**
     * @Route("/{activity}/{id}/context", name="oro_activity_context")
     *
     * @Template("@OroDataGrid/Grid/dialog/multi.html.twig")
     *
     * @param string $activity
     * @param string $id
     *
     * @return array
     *
     * @throws AccessDeniedException
     */
    public function contextAction($activity, $id)
    {
        $routingHelper = $this->get(EntityRoutingHelper::class);
        $entity        = $routingHelper->getEntity($activity, $id);
        $entityClass   = $routingHelper->resolveEntityClass($activity);

        if (!$this->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $entityClassAlias = $this->get(EntityAliasResolver::class)
            ->getPluralAlias($entityClass);

        return [
            'multiGridComponent'     => 'oroactivity/js/app/components/activity-context-component',
            'gridWidgetName'         => 'activity-context-grid',
            'dialogWidgetName'       => 'activity-context-dialog',
            'sourceEntity'           => $entity,
            'sourceEntityClassAlias' => $entityClassAlias,
            'entityTargets'          => $this->getSupportedTargets($entity),
            'params'                 => [
                'grid_query' => [
                    'params' => [
                        'activityClass' => $activity,
                        'activityId'    => $id,
                    ],
                ],
            ]
        ];
    }

    /**
     * @param object $entity
     *
     * @return array
     * [
     *     [
     *         'label' => label,
     *         'gridName' => gridName,
     *         'className' => className,
     *     ],
     * ]
     */
    protected function getSupportedTargets($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        $targetClasses = array_keys($this->getActivityManager()->getActivityTargets($entityClass));

        return $this->getMultiGridProvider()->getEntitiesData($targetClasses);
    }

    protected function getActivityManager(): ActivityManager
    {
        return $this->get(ActivityManager::class);
    }

    protected function getMultiGridProvider(): MultiGridProvider
    {
        return $this->get(MultiGridProvider::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ChainWidgetProvider::class,
                EntityRoutingHelper::class,
                EntityAliasResolver::class,
                ActivityManager::class,
                MultiGridProvider::class,
            ]
        );
    }
}
