<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

/**
 * This class responsible for binging EmailAddress to owner entities
 */
class EmailOwnerManager
{
    /**
     * A list of class names of all email owners
     *
     * @var string[]
     *      key   = owner field name
     *      value = owner class name
     */
    protected $emailOwnerClasses = array();

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * @var EntityRepository|null
     */
    private $emailAddressRepository;

    /**
     * Constructor.
     *
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param EmailAddressManager       $emailAddressManager
     */
    public function __construct(
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EmailAddressManager $emailAddressManager
    ) {
        foreach ($emailOwnerProviderStorage->getProviders() as $provider) {
            $fieldName                           = $emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            $this->emailOwnerClasses[$fieldName] = $provider->getEmailOwnerClass();
        }
        $this->emailAddressManager = $emailAddressManager;
    }

    /**
     * Handle onFlush event
     *
     * @param OnFlushEventArgs $event
     */
    public function handleOnFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $bindings = [
            // array of array('email' => email address, 'owner' => EmailOwnerInterface or false)
            'changes'   => [],
            // array of EmailOwnerInterface
            'deletions' => []
        ];

        $this->handleInsertionsOrUpdates($bindings, $uow->getScheduledEntityInsertions(), $uow);
        $this->handleInsertionsOrUpdates($bindings, $uow->getScheduledEntityUpdates(), $uow);
        $this->handleDeletions($bindings, $uow->getScheduledEntityDeletions());

        $this->persistBindings($bindings, $em);
    }

    /**
     * @param array      $bindings
     * @param array      $entities
     * @param UnitOfWork $uow
     */
    protected function handleInsertionsOrUpdates(array &$bindings, array $entities, UnitOfWork $uow)
    {
        foreach ($entities as $entity) {
            if ($entity instanceof EmailOwnerInterface) {
                $emailFields = $entity->getEmailFields();
                if (!empty($emailFields)) {
                    foreach ($emailFields as $emailField) {
                        $this->processInsertionOrUpdateEntity(
                            $bindings,
                            $emailField,
                            $entity,
                            $entity,
                            $uow
                        );
                    }
                }
            } elseif ($entity instanceof EmailInterface) {
                $this->processInsertionOrUpdateEntity(
                    $bindings,
                    $entity->getEmailField(),
                    $entity,
                    $entity->getEmailOwner(),
                    $uow
                );
            }
        }
    }

    /**
     * @param array               $bindings
     * @param                     $emailField
     * @param mixed               $entity
     * @param EmailOwnerInterface $owner
     * @param UnitOfWork          $uow
     */
    protected function processInsertionOrUpdateEntity(
        array &$bindings,
        $emailField,
        $entity,
        EmailOwnerInterface $owner,
        UnitOfWork $uow
    ) {
        $changeSet = $uow->getEntityChangeSet($entity);
        foreach ($changeSet as $field => $values) {
            if ($field === $emailField) {
                list($oldValue, $newValue) = $values;
                if ($newValue !== $oldValue) {
                    if (!empty($newValue)) {
                        $bindings['changes'][strtolower($newValue)] = [
                            'email' => $newValue,
                            'owner' => $owner
                        ];
                    }
                    if (!empty($oldValue) && !isset($bindings['changes'][strtolower($oldValue)])) {
                        $bindings['changes'][strtolower($oldValue)] = [
                            'email' => $oldValue,
                            'owner' => false
                        ];
                    }
                }
            }
        }
    }

    /**
     * @param array $bindings
     * @param array $entities
     */
    protected function handleDeletions(array &$bindings, array $entities)
    {
        foreach ($entities as $entity) {
            if ($entity instanceof EmailOwnerInterface) {
                $key                         = sprintf(
                    '%s:%d',
                    ClassUtils::getClass($entity),
                    $entity->getId()
                );
                $bindings['deletions'][$key] = $entity;
            } elseif ($entity instanceof EmailInterface) {
                $email = $entity->getEmail();
                if (!empty($email) && !isset($bindings['changes'][strtolower($email)])) {
                    $bindings['changes'][strtolower($email)] = [
                        'email' => $email,
                        'owner' => false
                    ];
                }
            }
        }
    }

    /**
     * @param array         $bindings
     * @param EntityManager $em
     */
    protected function persistBindings(array &$bindings, EntityManager $em)
    {
        foreach ($bindings['changes'] as $item) {
            $email = $item['email'];
            $owner = false === $item['owner'] ? null : $item['owner'];
            $emailAddress = $this->getEmailAddressRepository($em)->findOneBy(['email' => $email]);
            if ($emailAddress === null) {
                $emailAddress = $this->emailAddressManager->newEmailAddress()
                    ->setEmail($email)
                    ->setOwner($owner);
                $em->persist($emailAddress);
                $this->computeEntityChangeSet($em, $emailAddress);
            } elseif ($emailAddress->getOwner() !== $owner) {
                $emailAddress->setOwner($owner);
                $this->computeEntityChangeSet($em, $emailAddress);
            }
        }

        foreach ($bindings['deletions'] as $owner) {
            foreach ($this->emailOwnerClasses as $fieldName => $ownerClass) {
                if (is_a($owner, $ownerClass)) {
                    $condition = array($fieldName => $owner);
                    /** @var EmailAddress[] $emailAddresses */
                    $emailAddresses = $this->getEmailAddressRepository($em)->findBy($condition);
                    foreach ($emailAddresses as $emailAddress) {
                        $emailAddress->setOwner(null);
                        $this->computeEntityChangeSet($em, $emailAddress);
                    }
                }
            }
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param mixed         $entity
     */
    protected function computeEntityChangeSet(EntityManager $entityManager, $entity)
    {
        $entityClass   = ClassUtils::getClass($entity);
        $classMetadata = $entityManager->getClassMetadata($entityClass);
        $unitOfWork    = $entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSet($classMetadata, $entity);
    }

    /**
     * @param EntityManager $em
     *
     * @return EntityRepository
     */
    protected function getEmailAddressRepository(EntityManager $em)
    {
        if (null === $this->emailAddressRepository) {
            $this->emailAddressRepository = $this->emailAddressManager->getEmailAddressRepository($em);
        }

        return $this->emailAddressRepository;
    }
}
