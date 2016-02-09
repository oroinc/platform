<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class KnownEmailAddressChecker implements KnownEmailAddressCheckerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EntityManager */
    protected $em;

    /** @var EmailAddressManager */
    protected $emailAddressManager;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var array */
    protected $exclusions;

    /**
     * @var array
     *  key   = email address
     *  value = array
     *      known => true/false
     *      user  => user id
     */
    protected $emails = [];

    /**
     * Constructor
     *
     * @param EntityManager             $em
     * @param EmailAddressManager       $emailAddressManager
     * @param EmailAddressHelper        $emailAddressHelper
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param string[]                  $exclusions Class names of email address owners which should be excluded
     */
    public function __construct(
        EntityManager $em,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        array $exclusions = []
    ) {
        $this->em                        = $em;
        $this->emailAddressManager       = $emailAddressManager;
        $this->emailAddressHelper        = $emailAddressHelper;
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;

        $this->exclusions = [];
        if (!empty($exclusions)) {
            foreach ($exclusions as $className) {
                $this->exclusions[$className] = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAtLeastOneKnownEmailAddress($_)
    {
        $emailsToLoad = [];

        $args = func_get_args();
        foreach ($args as $arg) {
            if (empty($arg)) {
                continue;
            }
            foreach ($this->normalizeEmailAddresses((array)$arg) as $email) {
                if (empty($email)) {
                    continue;
                }
                if (isset($this->emails[$email])) {
                    if ($this->emails[$email]['known']) {
                        return true;
                    }
                } elseif (!isset($emailsToLoad[$email])) {
                    $emailsToLoad[$email] = $email;
                }
            }
        }

        if (!empty($emailsToLoad)) {
            $this->loadKnownEmailAddresses($emailsToLoad);
            foreach ($emailsToLoad as $email) {
                if ($this->emails[$email]['known']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAtLeastOneUserEmailAddress($userId, $_)
    {
        $emailsToLoad = [];

        $args = func_get_args();
        unset($args[0]);
        foreach ($args as $arg) {
            if (empty($arg)) {
                continue;
            }
            foreach ($this->normalizeEmailAddresses((array)$arg) as $email) {
                if (empty($email)) {
                    continue;
                }
                if (isset($this->emails[$email])) {
                    if (isset($this->emails[$email]['user'])
                        && $userId === $this->emails[$email]['user']
                    ) {
                        return true;
                    }
                } elseif (!isset($emailsToLoad[$email])) {
                    $emailsToLoad[$email] = $email;
                }
            }
        }

        if (!empty($emailsToLoad)) {
            $this->loadKnownEmailAddresses($emailsToLoad);
            foreach ($emailsToLoad as $email) {
                if ($this->emails[$email]['known']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preLoadEmailAddresses(array $emails)
    {
        $emailsToLoad = [];
        foreach ($emails as $arg) {
            if (empty($arg)) {
                continue;
            }
            foreach ($this->normalizeEmailAddresses((array)$arg) as $email) {
                if (!empty($email) && !isset($this->emails[$email]) && !isset($emailsToLoad[$email])) {
                    $emailsToLoad[$email] = $email;
                }
            }
        }

        if (!empty($emailsToLoad)) {
            $this->loadKnownEmailAddresses($emailsToLoad);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAtLeastOneMailboxEmailAddress($mailboxId, $_)
    {
        $emailsToLoad = [];

        $args = func_get_args();
        unset($args[0]);
        foreach ($args as $arg) {
            if (empty($arg)) {
                continue;
            }
            foreach ($this->normalizeEmailAddresses((array)$arg) as $email) {
                if (empty($email)) {
                    continue;
                }
                if (isset($this->emails[$email])) {
                    if (isset($this->emails[$email]['mailbox'])) {
                        return true;
                    }
                } elseif (!isset($emailsToLoad[$email])) {
                    $emailsToLoad[$email] = $email;
                }
            }
        }

        if (!empty($emailsToLoad)) {
            $this->loadKnownEmailAddresses($emailsToLoad);
            foreach ($emailsToLoad as $email) {
                if ($this->emails[$email]['known'] &&
                    isset($this->emails[$email]['mailbox']) &&
                    ($this->emails[$email]['mailbox'] === $mailboxId)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Loads the given emails into $this->emails
     *
     * @param string[] $emailsToLoad
     */
    protected function loadKnownEmailAddresses(array $emailsToLoad)
    {
        $this->logger->info(sprintf('Loading email address(es) "%s" ...', implode(',', $emailsToLoad)));

        $emails = $this->getKnownEmailAddresses($emailsToLoad);

        foreach ($emailsToLoad as $email) {
            $this->emails[$email] = isset($emails[$email])
                ? $emails[$email]
                : ['known' => false];
        }

        $this->logger->info(sprintf('Loaded %d email address(es).', count($emails)));
    }

    /**
     * @param string[] $emailsToLoad
     *
     * @return array
     *  key   = email address
     *  value = array
     *      known => true/false
     *          false if the address belongs to user only
     *          true if the address belongs to not excluded owners
     *      user  => user id
     */
    protected function getKnownEmailAddresses(array $emailsToLoad)
    {
        $repo = $this->emailAddressManager->getEmailAddressRepository($this->em);
        $qb   = $repo->createQueryBuilder('a')
            ->where('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->setParameter('hasOwner', true)
            ->setParameter('emails', $emailsToLoad);

        $select         = 'a.email';
        $userIdField    = null;
        $mailboxIdField = null;
        $ownerIdFields  = [];
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $ownerClass = $provider->getEmailOwnerClass();
            $isUser     = $ownerClass === 'Oro\Bundle\UserBundle\Entity\User';
            $isMailbox  = $ownerClass === 'Oro\Bundle\EmailBundle\Entity\Mailbox';
            $field      = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            if ($isUser) {
                $userIdField = $field;
            }
            if ($isMailbox) {
                $mailboxIdField = $field;
            }
            if (isset($this->exclusions[$ownerClass])) {
                if ($isUser) {
                    $select .= sprintf(',IDENTITY(a.%1$s) AS %1$s', $field);
                } else {
                    $qb->andWhere(sprintf('a.%s IS NULL', $field));
                }
            } else {
                $select .= sprintf(',IDENTITY(a.%1$s) AS %1$s', $field);
                $ownerIdFields[] = $field;
            }
        }
        $qb->select($select);

        $data   = $qb->getQuery()->getArrayResult();
        $result = $this->prepareKnownEmailAddressesData($data, $ownerIdFields, $userIdField, $mailboxIdField);

        return $result;
    }

    /**
     * @param string[] $emails
     *
     * @return string[]
     */
    protected function normalizeEmailAddresses(array $emails)
    {
        return array_map(
            function ($email) {
                return $this->normalizeEmailAddress($email);
            },
            $emails
        );
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function normalizeEmailAddress($email)
    {
        return strtolower($this->emailAddressHelper->extractPureEmailAddress($email));
    }

    /**
     * @param array $data
     * @param array $ownerIdFields
     * @param string $userIdField
     * @param string $mailboxIdField
     *
     * @return array
     */
    protected function prepareKnownEmailAddressesData($data, $ownerIdFields, $userIdField, $mailboxIdField)
    {
        $result = [];
        foreach ($data as $item) {
            $known = false;
            foreach ($ownerIdFields as $field) {
                if ($item[$field] !== null) {
                    $known = true;
                    break;
                }
            }

            $email = strtolower($item['email']);
            $userId = $item[$userIdField];
            $mailboxId = $item[$mailboxIdField];

            $result[$email] = $userId === null
                ? ['known' => $known]
                : ['known' => $known, 'user' => (int)$userId];

            if ($mailboxId !== null) {
                $result[$email]['mailbox'] = $mailboxId;
            }
        }

        return $result;
    }
}
