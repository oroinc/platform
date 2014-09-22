<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class KnownEmailAddressChecker
{
    /** @var LoggerInterface */
    protected $log;

    /** @var EntityManager */
    protected $em;

    /** @var EmailAddressManager */
    protected $emailAddressManager;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var array key = email address, value = 1 - known, -1 - unknown */
    protected $knownEmailAddresses = [];

    /**
     * Constructor
     *
     * @param LoggerInterface     $log
     * @param EntityManager       $em
     * @param EmailAddressManager $emailAddressManager
     * @param EmailAddressHelper  $emailAddressHelper
     */
    public function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper
    ) {
        $this->log                 = $log;
        $this->em                  = $em;
        $this->emailAddressManager = $emailAddressManager;
        $this->emailAddressHelper  = $emailAddressHelper;
    }

    /**
     * Check if at least one of the given email addresses is known
     *
     * @param mixed $_ Email address(es) to check
     *                 Each parameter can be a string or array of strings
     *
     * @return bool
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
     * Performs pre-loading of the given email addresses
     *
     * @param array $emails Each item can be a string or array of strings
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
        $this->log->notice(sprintf('Loading email address(es) "%s" ...', implode(',', $emailsToLoad)));

        $repo  = $this->emailAddressManager->getEmailAddressRepository($this->em);
        $query = $repo->createQueryBuilder('a')
            ->select('a.email')
            ->where('a.hasOwner = ?1 AND a.email IN (?2)')
            ->setParameter(1, true)
            ->setParameter(2, $emailsToLoad)
            ->getQuery();
        $query->setHydrationMode(Query::HYDRATE_ARRAY);

        $emails           = $query->getResult();
        $loadedEmailCount = count($emails);

        foreach ($emails as $item) {
            $email = strtolower($item['email']);

            $this->knownEmailAddresses[$email] = 1; // known
            unset($emailsToLoad[$email]);
        }
        foreach ($emailsToLoad as $email) {
            $this->knownEmailAddresses[$email] = -1; // unknown
        }

        $this->log->notice(sprintf('Loaded %d email address(es).', $loadedEmailCount));
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
