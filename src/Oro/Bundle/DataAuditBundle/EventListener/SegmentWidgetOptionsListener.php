<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\SegmentWidget\ContextChecker;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Handles {@see WidgetOptionsLoadEvent} to extend segment widgets with data audit functionality.
 *
 * This listener enhances segment widgets by adding the data audit component extension when the user
 * has appropriate permissions and the context allows it. The extension provides UI capabilities for
 * viewing and filtering audit history within segment widgets, enabling users to analyze entity changes
 * directly from the segment interface.
 */
class SegmentWidgetOptionsListener
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ContextChecker */
    protected $contextChecker;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ContextChecker $contextChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->contextChecker = $contextChecker;
    }

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
