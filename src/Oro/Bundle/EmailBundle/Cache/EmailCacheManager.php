<?php

namespace Oro\Bundle\EmailBundle\Cache;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;

class EmailCacheManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EmailBodyLoaderSelector */
    protected $selector;

    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * Constructor.
     *
     * @param EmailBodyLoaderSelector  $selector
     * @param EntityManager            $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EmailBodyLoaderSelector $selector,
        EntityManager $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->selector = $selector;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Check that email body is cached.
     * If do not, load it using appropriate email extension add it to a cache.
     *
     * @param Email $email
     *
     * @throws LoadEmailBodyException if a body of the given email cannot be loaded
     */
    public function ensureEmailBodyCached(Email $email)
    {
        if ($email->getEmailBody() === null) {
            // body loader can load email from any folder
            // todo: refactor to use correct emailuser and origin
            // to use active origin and get correct folder from this origin
            $emailUser = $email->getEmailUsers()->first();
            $folder = $emailUser->getFolders()->first();
            $origin = $emailUser->getOrigin();
            if (!$origin) {
                throw new LoadEmailBodyFailedException($email);
            }
            $loader = $this->selector->select($origin);

            try {
                $emailBody = $loader->loadEmailBody($folder, $email, $this->em);
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

            $email->setEmailBody($emailBody);

            $this->em->persist($email);
            $this->em->flush();

            $event = new EmailBodyAdded($email);
            $this->eventDispatcher->dispatch(EmailBodyAdded::NAME, $event);
        }

        $this->eventDispatcher->dispatch(EmailBodyLoaded::NAME, new EmailBodyLoaded($email));
    }
}
