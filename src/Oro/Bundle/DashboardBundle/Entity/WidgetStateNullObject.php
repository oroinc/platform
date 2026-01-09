<?php

namespace Oro\Bundle\DashboardBundle\Entity;

/**
 * Represents a null object for widget state when no user is authenticated.
 *
 * This class implements the Null Object pattern to provide a safe default widget state
 * that can be used when there is no authenticated user. It prevents the need for null checks
 * throughout the codebase while ensuring widget state operations remain safe and predictable.
 */
class WidgetStateNullObject extends WidgetState
{
}
