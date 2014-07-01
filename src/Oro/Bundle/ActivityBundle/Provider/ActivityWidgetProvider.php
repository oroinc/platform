<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\EntityIdentifierAccessor;
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

    /** @var EntityIdentifierAccessor */
    protected $entityIdentifierAccessor;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param ActivityManager          $activityManager
     * @param SecurityFacade           $securityFacade
     * @param TranslatorInterface      $translator
     * @param EntityIdentifierAccessor $entityIdentifierAccessor
     * @param EntityRoutingHelper      $entityRoutingHelper
     */
    public function __construct(
        ActivityManager $activityManager,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        EntityIdentifierAccessor $entityIdentifierAccessor,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->activityManager          = $activityManager;
        $this->securityFacade           = $securityFacade;
        $this->translator               = $translator;
        $this->entityIdentifierAccessor = $entityIdentifierAccessor;
        $this->entityRoutingHelper      = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return $this->activityManager->hasActivityAssociations(ClassUtils::getClass($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $result = [];

        $entityClass = ClassUtils::getClass($entity);
        $entityId    = $this->entityIdentifierAccessor->getIdentifier($entity);

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
