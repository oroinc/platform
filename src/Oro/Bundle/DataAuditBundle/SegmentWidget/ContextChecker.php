<?php

namespace Oro\Bundle\DataAuditBundle\SegmentWidget;

/**
 * Checks whether data audit functionality should be enabled in a given context.
 *
 * This service evaluates context parameters to determine if audit features (such as the audit segment
 * widget extension) should be activated. It allows for selective disabling of audit functionality in
 * specific contexts where it may not be relevant or desired, providing flexibility in how audit features
 * are presented across different parts of the application.
 */
class ContextChecker
{
    const DISABLED_PARAM = 'disable_audit';

    /**
     * @param array $context
     *
     * @return bool
     */
    public function isApplicableInContext(array $context)
    {
        return empty($context[self::DISABLED_PARAM]);
    }
}
