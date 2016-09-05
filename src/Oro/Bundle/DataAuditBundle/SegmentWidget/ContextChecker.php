<?php

namespace Oro\Bundle\DataAuditBundle\SegmentWidget;

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
