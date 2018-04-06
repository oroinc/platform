<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

abstract class AbstractAuditProcessor implements MessageProcessorInterface
{
    /**
     * @param array $message
     *
     * @return \DateTime
     */
    protected function getLoggedAt(array $message)
    {
        return \DateTime::createFromFormat('U', $message['timestamp']);
    }

    /**
     * @param array $message
     *
     * @return string
     */
    protected function getTransactionId(array $message)
    {
        return $message['transaction_id'];
    }

    /**
     * @param array $message
     *
     * @return EntityReference
     */
    protected function getUserReference(array $message)
    {
        return $this->getEntityReference($message, 'user_id', $this->getValue($message, 'user_class'));
    }

    /**
     * @param array $message
     *
     * @return EntityReference
     */
    protected function getOrganizationReference(array $message)
    {
        return $this->getEntityReference($message, 'organization_id', Organization::class);
    }

    /**
     * @param array $message
     *
     * @return EntityReference
     */
    protected function getImpersonationReference(array $message)
    {
        return $this->getEntityReference($message, 'impersonation_id', Impersonation::class);
    }

    /**
     * @param array $message
     *
     * @return string|null
     */
    protected function getOwnerDescription(array $message)
    {
        return $this->getValue($message, 'owner_description');
    }

    /**
     * @param array  $message
     * @param string $key
     *
     * @return mixed
     */
    protected function getValue(array $message, $key)
    {
        if (!isset($message[$key])) {
            return null;
        }

        return $message[$key];
    }

    /**
     * @param array  $message
     * @param string $entityIdKey
     * @param string $entityClass
     *
     * @return EntityReference
     */
    protected function getEntityReference(array $message, $entityIdKey, $entityClass)
    {
        if ($entityClass && isset($message[$entityIdKey])) {
            return new EntityReference($entityClass, $message[$entityIdKey]);
        }

        return new EntityReference();
    }
}
