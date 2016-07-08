<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;

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

    /** @var EntityManager */
    protected $manager = null;

    /**
     * EmailBodySynchronizer constructor.
     *
     * @param EmailBodyLoaderSelector  $selector
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $eventDispatcher
     */
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
     * Syncs email body for one email
     *
     * @param Email $email
     */
    public function syncOneEmailBody(Email $email)
    {
        if ($this->isBodyNotLoaded($email)) {
            // body loader can load email from any folder
            // todo: refactor to use correct emailuser and origin
            // to use active origin and get correct folder from this origin
            $emailUser = $email->getEmailUsers()->first();
            $folder    = $emailUser->getFolders()->first();
            $origin    = $emailUser->getOrigin();
            if (!$origin) {
                throw new LoadEmailBodyFailedException($email);
            }

            $loader    = $this->getBodyLoader($origin);
            $bodyLoaded = false;
            try {
                $em        = $this->getManager();
                $emailBody = $loader->loadEmailBody($folder, $email, $em);
                $em->refresh($email);
                // double check
                if ($this->isBodyNotLoaded($email)) {
                    $email->setEmailBody($emailBody);
                    $email->setBodySynced(true);
                    $bodyLoaded = true;
                    $em->persist($email);
                    $em->flush($email);
                }
            } catch (LoadEmailBodyException $loadEx) {
                $this->logger->notice(
                    sprintf('Load email body failed. Email id: %d. Error: %s', $email->getId(), $loadEx->getMessage()),
                    ['exception' => $loadEx]
                );
                throw $loadEx;
            } catch (\Exception $ex) {
                $this->logger->warning(
                    sprintf('Load email body failed. Email id: %d. Error: %s.', $email->getId(), $ex->getMessage()),
                    ['exception' => $ex]
                );
                throw new LoadEmailBodyFailedException($email, $ex);
            }
            if ($bodyLoaded) {
                $event = new EmailBodyAdded($email);
                $this->eventDispatcher->dispatch(EmailBodyAdded::NAME, $event);
            }
        }
    }

    /**
     * Syncs email bodies
     *
     * @param int $maxExecTimeInMin
     * @param int $batchSize
     */
    public function sync($maxExecTimeInMin = -1, $batchSize = 10)
    {
        $repo           = $this->doctrine->getRepository('OroEmailBundle:Email');
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

            $emails = $repo->getEmailsWithoutBody($batchSize);
            if (count($emails) === 0) {
                $this->logger->info('All emails was processed');
                break;
            }

            $batchStartTime = new \DateTime('now', new \DateTimeZone('UTC'));

            /** @var Email $email */
            foreach ($emails as $email) {
                try {
                    $this->syncOneEmailBody($email);
                    $this->logger->notice(
                        sprintf('The "%s" (ID: %d) email body was synced.', $email->getSubject(), $email->getId())
                    );
                } catch (\Exception $e) {
                    // in case of exception, we should save state that email body was synced.
                    $this->getManager()->persist($email);
                    continue;
                }
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
        if (!$this->manager) {
            $this->manager = $this->doctrine->getManager();
        }

        return $this->manager;
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
     *
     * @return bool
     */
    protected function isBodyNotLoaded(Email $email)
    {
        return $email->isBodySynced() !== true && $email->getEmailBody() === null;
    }
}
