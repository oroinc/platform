<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;

class ActivityWidgetProvider implements WidgetProviderInterface
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param ActivityManager     $activityManager
     * @param SecurityFacade      $securityFacade
     * @param TranslatorInterface $translator
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        ActivityManager $activityManager,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->activityManager     = $activityManager;
        $this->securityFacade      = $securityFacade;
        $this->translator          = $translator;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return $this->activityManager->hasActivityAssociations(
            $this->entityRoutingHelper->getEntityClass($entity)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $result = [];

        $entityClass = $this->entityRoutingHelper->getEntityClass($entity);
        $entityId    = $this->entityRoutingHelper->getSingleEntityIdentifier($entity);

        $items = $this->activityManager->getActivityAssociations($entityClass);
        foreach ($items as $item) {
            if (empty($item['acl']) || $this->securityFacade->isGranted($item['acl'])) {
                $url    = $this->entityRoutingHelper->generateUrl($item['route'], $entityClass, $entityId);
                $widget = [
                    'widgetType' => 'block',
                    'alias'      => $item['associationName'],
                    'label'      => $this->translator->trans($item['label']),
                    'url'        => $url
                ];
                if (isset($item['priority'])) {
                    $widget['priority'] = $item['priority'];
                }
                $result[] = $widget;
            }
        }

        return $result;
    }
}
