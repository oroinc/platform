<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

/**
 * This class responsible for binding EmailAddress to owner entities
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
     * @param array $emailAddressData Data retrieved by "createEmailAddressData"
     *
     * @return array Updated email addresses
     */
    public function handleChangedAddresses(array $emailAddressData)
    {
        $emailOwnerChanges = $this->getEmailOwnerChanges($emailAddressData['updates']);
        $emailOwnerDeletions = $this->handleDeletions($emailOwnerChanges, $emailAddressData['deletions']);

        return $this->updateEmailAddresses($emailOwnerChanges, $emailOwnerDeletions);
    }

    /**
     * Creates data
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function createEmailAddressData(UnitOfWork $uow)
    {
        return [
            'updates' => array_map(
                function ($entity) use ($uow) {
                    return [
                        'entity' => $entity,
                        'changeSet' => $uow->getEntityChangeSet($entity),
                    ];
                },
                array_filter(
                    array_merge(
                        $uow->getScheduledEntityInsertions(),
                        $uow->getScheduledEntityUpdates()
                    ),
                    $this->getEntityFilter()
                )
            ),
            'deletions' => array_filter(
                $uow->getScheduledEntityDeletions(),
                $this->getEntityFilter()
            )
        ];
    }

    /**
     * @param array $entities
     *
     * @return array
     */
    protected function getEmailOwnerChanges(array $entities)
    {
        $emailOwnerChanges = [];
        foreach ($entities as $data) {
            $entity = $data['entity'];
            $changeSet = $data['changeSet'];
            $ownerData = $this->createEmailOwnerData($entity);

            foreach ($ownerData['emailFields'] as $emailField) {
                $this->processEntityChanges(
                    $emailOwnerChanges,
                    $emailField,
                    $ownerData['owner'],
                    $changeSet
                );
            }
        }

        return $emailOwnerChanges;
    }

    /**
     * @param object $entity
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function createEmailOwnerData($entity)
    {
        if ($entity instanceof EmailOwnerInterface) {
            return [
                'emailFields' => $entity->getEmailFields() ?: [],
                'owner' => $entity,
            ];
        }

        if ($entity instanceof EmailInterface) {
            return [
                'emailFields' => [$entity->getEmailField()],
                'owner' => $entity->getEmailOwner(),
            ];
        }

        throw new \InvalidArgumentException(
            'Entity is expected to be type one of types: "%s" but "%s" given.',
            'Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface, Oro\Bundle\EmailBundle\Entity\EmailInterface',
            ClassUtils::getClass($entity)
        );
    }

    /**
     * @param array               $emailOwnerData
     * @param                     $emailField
     * @param EmailOwnerInterface $owner
     * @param array               $changeSet
     */
    protected function processEntityChanges(
        array &$emailOwnerData,
        $emailField,
        EmailOwnerInterface $owner,
        array $changeSet
    ) {
        if (!array_key_exists($emailField, $changeSet)) {
            return;
        }

        $values = $changeSet[$emailField];
        list($oldValue, $newValue) = $values;
        if ($newValue === $oldValue) {
            return;
        }

        if (!empty($newValue)) {
            $emailOwnerData[strtolower($newValue)] = [
                'email' => $newValue,
                'owner' => $owner
            ];
        }
        if (!empty($oldValue) && !isset($emailOwnerData[strtolower($oldValue)])) {
            $emailOwnerData[strtolower($oldValue)] = [
                'email' => $oldValue,
                'owner' => false
            ];
        }
    }

    /**
     * @param array $emailOwnerChanges
     * @param array $entities
     *
     * @return EmailOwnerInterface[]
     */
    protected function handleDeletions(array &$emailOwnerChanges, array $entities)
    {
        $emailOwnerDeletions = [];
        foreach ($entities as $entity) {
            if ($entity instanceof EmailOwnerInterface) {
                $key                         = sprintf(
                    '%s:%d',
                    ClassUtils::getClass($entity),
                    $entity->getId()
                );
                $emailOwnerDeletions[$key] = $entity;
            } elseif ($entity instanceof EmailInterface) {
                $email = $entity->getEmail();
                if (!empty($email) && !isset($emailOwnerChanges[strtolower($email)])) {
                    $emailOwnerChanges[strtolower($email)] = [
                        'email' => $email,
                        'owner' => false
                    ];
                }
            }
        }

        return $emailOwnerDeletions;
    }

    /**
     * @param array $emailOwnerChanges
     *
     * @return EmailAddress[]
     */
    protected function updateEmailAddresses(array $emailOwnerChanges, array $emailOwnerDeletions)
    {
        $updatedEmailAddresses = [];
        $createEmailAddresses = [];
        foreach ($emailOwnerChanges as $item) {
            $email = $item['email'];
            $newOwner = false === $item['owner'] ? null : $item['owner'];
            $emailAddress = $this->emailAddressManager->getEmailAddressRepository()->findOneBy(['email' => $email]);
            if ($emailAddress === null) {
                $emailAddress = $this->emailAddressManager->newEmailAddress()
                    ->setEmail($email)
                    ->setOwner($newOwner);
                $createEmailAddresses[] = $emailAddress;
            } elseif ($emailAddress->getOwner() !== $newOwner) {
                $emailAddress->setOwner($newOwner);
                $updatedEmailAddresses[] = $emailAddress;
            }
        }

        foreach ($emailOwnerDeletions as $owner) {
            foreach ($this->emailOwnerClasses as $fieldName => $ownerClass) {
                if (is_a($owner, $ownerClass)) {
                    $condition = array($fieldName => $owner);
                    /* @var $emailAddresses EmailAddress[] */
                    $emailAddresses = $this->emailAddressManager->getEmailAddressRepository()->findBy($condition);
                    foreach ($emailAddresses as $emailAddress) {
                        $emailAddress->setOwner(null);
                        $updatedEmailAddresses[] = $emailAddress;
                    }
                }
            }
        }

        return [$updatedEmailAddresses, $createEmailAddresses];
    }

    /**
     * @return \Closure
     */
    protected function getEntityFilter()
    {
        return function ($entity) {
            return $entity instanceof EmailOwnerInterface || $entity instanceof EmailInterface;
        };
    }
}
