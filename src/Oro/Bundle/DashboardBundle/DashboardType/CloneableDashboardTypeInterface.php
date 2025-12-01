<?php

namespace Oro\Bundle\DashboardBundle\DashboardType;

/**
 * Interface for dashboard type providers that support cloning from another dashboard.
 */
interface CloneableDashboardTypeInterface
{
    public function isCloneable(): bool;
}
