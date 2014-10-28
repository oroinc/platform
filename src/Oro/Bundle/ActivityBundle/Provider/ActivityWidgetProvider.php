<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
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

    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param ActivityManager     $activityManager
     * @param SecurityFacade      $securityFacade
     * @param TranslatorInterface $translator
     * @param EntityIdAccessor    $entityIdAccessor
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        ActivityManager $activityManager,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        EntityIdAccessor $entityIdAccessor,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->activityManager     = $activityManager;
        $this->securityFacade      = $securityFacade;
        $this->translator          = $translator;
        $this->entityIdAccessor    = $entityIdAccessor;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return $this->activityManager->hasActivityAssociations(ClassUtils::getClass($object));
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($object)
    {
        $result = [];

        $entityClass = ClassUtils::getClass($object);
        $entityId    = $this->entityIdAccessor->getIdentifier($object);

        $items = $this->activityManager->getActivityAssociations($entityClass);
        foreach ($items as $item) {
            if (empty($item['acl']) || $this->securityFacade->isGranted($item['acl'])) {
                $url    = $this->entityRoutingHelper->generateUrl($item['route'], $entityClass, $entityId);
                $alias  = sprintf(
                    '%s_%s_%s',
                    strtolower(ExtendHelper::getShortClassName($item['className'])),
                    dechex(crc32($item['className'])),
                    $item['associationName']
                );
                $widget = [
                    'widgetType' => 'block',
                    'alias'      => $alias,
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
