<?php

namespace Oro\Bundle\DataAuditBundle\Service;

use Psr\Log\LoggerInterface;

/**
 * A service class to validate the data audit records before they will be added to the database.
 */
class AuditRecordValidator
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Validates that the given record has "entity_class" and "entity_id" attributes
     * and their values are not empty.
     *
     * @param array       $record
     * @param string|null $action
     *
     * @return bool
     */
    public function validateAuditRecord(array $record, $action = null)
    {
        $isValid = true;
        if (!array_key_exists('entity_class', $record) || !$record['entity_class']) {
            $this->logError('The "entity_class" must not be empty.', $record, $action);
            $isValid = false;
        } elseif (!array_key_exists('entity_id', $record) || null === $record['entity_id']) {
            $this->logError('The "entity_id" must not be null.', $record, $action);
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param string      $message
     * @param array       $record
     * @param string|null $action
     */
    private function logError($message, $record, $action)
    {
        $context = ['audit_record' => $record];
        if ($action) {
            $context['audit_action'] = $action;
        }
        $this->logger->error($message, $context);
    }
}
