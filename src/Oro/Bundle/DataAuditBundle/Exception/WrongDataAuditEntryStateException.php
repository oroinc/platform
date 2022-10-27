<?php

namespace Oro\Bundle\DataAuditBundle\Exception;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;

/**
 * Exception that should be thrown when data must be reprocessed.
 */
class WrongDataAuditEntryStateException extends \RuntimeException
{
    public function __construct(AbstractAudit $audit)
    {
        $message = sprintf('Wrong data audit entry state for "%s" object name', $audit->getObjectName());

        parent::__construct($message);
    }
}
