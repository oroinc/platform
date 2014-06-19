<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActivityWidgetProvider implements ActivityWidgetProviderInterface
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $result = [];

        list($entityClass, $entityId) = $this->entityRoutingHelper->getEntityClassAndId($entity);

        $activities = $this->activityManager->getAssociatedActivityInfo($entityClass);
        foreach ($activities as $activity) {
            if (empty($activity['acl']) || $this->securityFacade->isGranted($activity['acl'])) {
                $url    = $this->entityRoutingHelper->generateUrl($activity['route'], $entityClass, $entityId);
                $widget = [
                    'widgetType' => 'block',
                    'alias'      => $activity['associationName'],
                    'label'      => $this->translator->trans($activity['label']),
                    'url'        => $url,
                ];
                if (isset($activity['priority'])) {
                    $widget['priority'] = $activity['priority'];
                }
                $result[] = $widget;
            }
        }

        return $result;
    }
}
