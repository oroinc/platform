<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;

/**
 * Provides a set of methods to work with EmailAddress entity.
 */
class EmailAddressManager
{
    private string $entityCacheNamespace;
    private string $entityProxyNameTemplate;
    private ManagerRegistry $doctrine;
    private ?string $emailAddressProxyClass = null;

    public function __construct(
        string $entityCacheNamespace,
        string $entityProxyNameTemplate,
        ManagerRegistry $doctrine
    ) {
        $this->entityCacheNamespace = $entityCacheNamespace;
        $this->entityProxyNameTemplate = $entityProxyNameTemplate;
        $this->doctrine = $doctrine;
    }

    /**
     * Creates EmailAddress entity object.
     * Actually a proxy class for this entity is created.
     */
    public function newEmailAddress(): EmailAddress
    {
        $emailAddressClass = $this->getEmailAddressProxyClass();

        return new $emailAddressClass();
    }

    /**
     * Gets an entity manager for EmailAddress entity.
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($this->getEmailAddressProxyClass());
    }

    /**
     * Gets an entity repository for EmailAddress entity.
     */
    public function getEmailAddressRepository(EntityManagerInterface $em = null): EntityRepository
    {
        if (null === $em) {
            $em = $this->getEntityManager();
        }

        return $em->getRepository($this->getEmailAddressProxyClass());
    }

    /**
     * Gets the full class name of a proxy for EmailAddress entity.
     */
    public function getEmailAddressProxyClass(): string
    {
        if (null === $this->emailAddressProxyClass) {
            $this->emailAddressProxyClass = sprintf(
                '%s\%s',
                $this->entityCacheNamespace,
                sprintf($this->entityProxyNameTemplate, 'EmailAddress')
            );
        }

        return $this->emailAddressProxyClass;
    }
}
