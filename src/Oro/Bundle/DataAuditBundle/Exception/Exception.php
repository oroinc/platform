<?php

namespace Oro\Bundle\DataAuditBundle\Exception;

/**
 * Marker interface for all exceptions thrown by the DataAuditBundle.
 *
 * This interface allows catching all data audit-related exceptions in a unified way,
 * facilitating error handling and logging specific to audit operations. Implementing
 * this interface on custom exceptions ensures they are recognized as part of the
 * data audit subsystem.
 */
interface Exception
{
}
