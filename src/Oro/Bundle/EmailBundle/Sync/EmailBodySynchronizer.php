<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Synchronizer that syncs the email body data.
 */
class EmailBodySynchronizer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EmailBodyLoaderSelector */
    protected $selector;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $emailBodyLoaders = [];

    /**
     * @var EntityManager
     * @deprecated
     */
    protected $manager = null;

    public function __construct(
        EmailBodyLoaderSelector $selector,
        ManagerRegistry $doctrine,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->selector        = $selector;
        $this->doctrine        = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Syncs email body for one email.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function syncOneEmailBody(Email $email, $forceSync = false)
    {
        if ($this->isBodyNotLoaded($email, $forceSync)) {
            // Body loader can load email body from any folder of any emailUser.
            // Even if email body was not loaded, email will be marked as synced to prevent sync degradation in time.
            $em = $this->getManager();
            $bodyLoaded = false;
            foreach ($email->getEmailUsers() as $emailUser) {
                if (($origin = $emailUser->getOrigin()) && $origin->isActive()) {
                    foreach ($emailUser->getFolders() as $folder) {
                        list($bodyLoaded, $emailBodyChanged) = $this->loadBody($email, $forceSync, $origin, $folder);
                        if ($emailBodyChanged) {
                            $event = new EmailBodyAdded($email);
                            try {
                                $this->eventDispatcher->dispatch($event, EmailBodyAdded::NAME);
                            } catch (\Exception $e) {
                                $bodyLoaded = false;
                                $this->processFailedDuringSaveEmail($email, $e);
                            }
                            break 2;
                        }
                    }
                }
            }
            if (!$bodyLoaded) {
                $this->updateBodySyncedStateForEntity($email);
            } else {
                $email->setBodySynced(true);
                try {
                    $em->persist($email);
                    $em->flush($email);
                    $this->logger->notice(
                        sprintf('The "%s" (ID: %d) email body was synced.', $email->getSubject(), $email->getId())
                    );
                } catch (\Exception $e) {
                    $this->processFailedDuringSaveEmail($email, $e);
                }
            }
        }
    }

    /**
     * Syncs email bodies.
     */
    public function sync($maxExecTimeInMin = -1, $batchSize = 10)
    {
        $maxExecTimeout = $maxExecTimeInMin > 0
            ? new \DateInterval('PT' . $maxExecTimeInMin . 'M')
            : false;

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));

        while (true) {
            if ($maxExecTimeout !== false) {
                $date = new \DateTime('now', new \DateTimeZone('UTC'));
                if ($date->sub($maxExecTimeout) >= $startTime) {
                    $this->logger->notice('Exit because allocated time frame elapsed.');
                    break;
                }
            }

            $emailIds = $this->doctrine->getRepository('OroEmailBundle:Email')->getEmailIdsWithoutBody($batchSize);
            if (count($emailIds) === 0) {
                $this->logger->info('All emails was processed');
                break;
            }

            $batchStartTime = new \DateTime('now', new \DateTimeZone('UTC'));

            /** @var Email $email */
            foreach ($emailIds as $emailId) {
                $em = $this->getManager();
                $email = $em->find(Email::class, $emailId);
                $this->syncOneEmailBody($email);
            }
            $this->getManager()->clear();

            $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $diff        = $currentTime->diff($batchStartTime);
            $this->logger->info(sprintf('Batch save time: %s.', $diff->format('%i minutes %s seconds')));
        }
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        $manager = $this->doctrine->getManager();
        if (!$manager->isOpen()) {
            $manager = $this->doctrine->resetManager();
            $manager->clear();

            return $manager;
        }

        return $manager;
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return EmailBodyLoaderInterface
     */
    protected function getBodyLoader(EmailOrigin $origin)
    {
        $originId = $origin->getId();
        if (!isset($this->emailBodyLoaders[$originId])) {
            $this->emailBodyLoaders[$originId] = $this->selector->select($origin);
        }

        return $this->emailBodyLoaders[$originId];
    }

    /**
     * @param Email $email
     * @param bool $forceSync
     *
     * @return bool
     */
    protected function isBodyNotLoaded(Email $email, $forceSync)
    {
        return ($email->isBodySynced() !== true || $forceSync === true) && $email->getEmailBody() === null;
    }

    /**
     * @param Email $email
     * @param bool $forceSync
     * @param EmailOrigin $origin
     * @param EmailFolder $folder
     *
     * @return array
     *
     * @throws LoadEmailBodyFailedException
     */
    protected function loadBody(Email $email, $forceSync, $origin, $folder)
    {
        $bodyLoaded = false;
        $emailBodyChanged = false;
        $em = $this->getManager();
        $loader = $this->getBodyLoader($origin);
        try {
            $emailBody = $loader->loadEmailBody($folder, $email, $em);
            $bodyLoaded = true;
            // double check
            if ($this->isBodyNotLoaded($email, $forceSync)) {
                $email->setEmailBody($emailBody);
                $emailBodyChanged = true;
            }
        } catch (EmailBodyNotFoundException $e) {
            $this->logger->notice(
                sprintf(
                    'Attempt to load email body from remote server failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        } catch (\Doctrine\ORM\NoResultException $e) {
            $this->logger->notice(
                sprintf(
                    'Attempt to load email body failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        } catch (LoadEmailBodyException $loadEx) {
            $this->logger->notice(
                sprintf(
                    'Load email body failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $loadEx->getMessage()
                ),
                ['exception' => $loadEx]
            );
            throw $loadEx;
        } catch (\Exception $ex) {
            $this->logger->info(
                sprintf(
                    'Load email body failed. Email id: %d. Error: %s.',
                    $email->getId(),
                    $ex->getMessage()
                ),
                ['exception' => $ex]
            );
        }

        return [$bodyLoaded, $emailBodyChanged];
    }

    private function updateBodySyncedStateForEntity(Email $email)
    {
        // in case of exception during the entity save, we should save state that email body was synced
        // to prevent sync degradation in time.
        $em = $this->getManager();
        $tableName = $em->getClassMetadata(Email::class)->getTableName();
        $connection = $em->getConnection();
        $connection->update($tableName, ['body_synced' => true], ['id' => $email->getId()]);
    }

    private function processFailedDuringSaveEmail(Email $email, \Exception $exception)
    {
        $this->updateBodySyncedStateForEntity($email);
        $this->logger->info(
            sprintf('Load email body failed. Email id: %d. Error: %s', $email->getId(), $exception->getMessage()),
            ['exception' => $exception]
        );
    }
}
