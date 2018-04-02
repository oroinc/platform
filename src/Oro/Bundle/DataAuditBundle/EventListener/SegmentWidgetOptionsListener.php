<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\SegmentWidget\ContextChecker;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SegmentWidgetOptionsListener
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ContextChecker */
    protected $contextChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ContextChecker                $contextChecker
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ContextChecker $contextChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->contextChecker = $contextChecker;
    }

    /**
     * @param WidgetOptionsLoadEvent $event
     */
    public function onLoad(WidgetOptionsLoadEvent $event)
    {
        if (!$this->authorizationChecker->isGranted('oro_dataaudit_view')) {
            return;
        }
        $widgetOptions = $event->getWidgetOptions();
        if (!$this->contextChecker->isApplicableInContext($widgetOptions)) {
            return;
        }

        $event->setWidgetOptions(array_merge_recursive(
            $widgetOptions,
            [
                'extensions' => [
                    'orodataaudit/js/app/components/segment-component-extension',
                ],
            ]
        ));
    }
}
