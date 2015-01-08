<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

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

    /** @var array key = email address, value = 1 - known, -1 - unknown */
    protected $knownEmailAddresses = [];

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
        $exclusions = []
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
                if (isset($this->knownEmailAddresses[$email])) {
                    if ($this->knownEmailAddresses[$email] === 1) {
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
                if (isset($this->knownEmailAddresses[$email]) && $this->knownEmailAddresses[$email] === 1) {
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
                if (!empty($email)
                    && !isset($this->knownEmailAddresses[$email])
                    && !isset($emailsToLoad[$email])
                ) {
                    $emailsToLoad[$email] = $email;
                }
            }
        }

        if (!empty($emailsToLoad)) {
            $this->loadKnownEmailAddresses($emailsToLoad);
        }
    }

    /**
     * Loads the given emails into $this->knownEmailAddresses
     *
     * @param string[] $emailsToLoad
     */
    protected function loadKnownEmailAddresses(array $emailsToLoad)
    {
        $this->logger->notice(sprintf('Loading email address(es) "%s" ...', implode(',', $emailsToLoad)));

        $emails = $this->getKnownEmailAddresses($emailsToLoad);

        $loadedEmailCount = count($emails);

        foreach ($emails as $item) {
            $email = strtolower($item['email']);

            $this->knownEmailAddresses[$email] = 1; // known
            unset($emailsToLoad[$email]);
        }
        foreach ($emailsToLoad as $email) {
            $this->knownEmailAddresses[$email] = -1; // unknown
        }

        $this->logger->notice(sprintf('Loaded %d email address(es).', $loadedEmailCount));
    }

    /**
     * @param string[] $emailsToLoad
     *
     * @return array
     */
    protected function getKnownEmailAddresses(array $emailsToLoad)
    {
        $repo = $this->emailAddressManager->getEmailAddressRepository($this->em);
        $qb   = $repo->createQueryBuilder('a')
            ->select('a.email')
            ->where('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->setParameter('hasOwner', true)
            ->setParameter('emails', $emailsToLoad);

        if (!empty($this->exclusions)) {
            foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
                if (isset($this->exclusions[$provider->getEmailOwnerClass()])) {
                    $fieldName = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
                    $qb->andWhere(sprintf('a.%s IS NULL', $fieldName));
                }
            }
        }

        return $qb->getQuery()->getArrayResult();
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
}
