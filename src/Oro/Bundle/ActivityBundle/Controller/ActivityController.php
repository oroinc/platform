<?php

namespace Oro\Bundle\ActivityBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Provider\MultiGridProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/activities")
 */
class ActivityController extends Controller
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
        $widgetProvider = $this->get('oro_activity.widget_provider.activities');

        $widgets = $widgetProvider->supports($entity)
            ? $widgetProvider->getWidgets($entity)
            : [];

        if (empty($widgets)) {
            // return empty response to prevent rendering 'Activities' placeholder
            return new Response();
        }

        return $this->render('OroActivityBundle:Activity:activities.html.twig', ['tabs' => $widgets]);
    }

    /**
     * @Route("/{activity}/{id}/context", name="oro_activity_context")
     *
     * @Template("OroDataGridBundle:Grid/dialog:multi.html.twig")
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
        $routingHelper = $this->get('oro_entity.routing_helper');
        $entity        = $routingHelper->getEntity($activity, $id);
        $entityClass   = $routingHelper->resolveEntityClass($activity);

        if (!$this->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $entityClassAlias = $this->get('oro_entity.entity_alias_resolver')
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

    /**
     * @return ActivityManager
     */
    protected function getActivityManager()
    {
        return $this->get('oro_activity.manager');
    }

    /**
     * @return MultiGridProvider
     */
    protected function getMultiGridProvider()
    {
        return $this->get('oro_datagrid.multi_grid_provider');
    }
}
