<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Replace found EmailUser entity with related Email entity.
 */
class PrepareResultItemListener
{
    private UrlGeneratorInterface $urlGenerator;
    private ManagerRegistry $doctrine;

    public function __construct(UrlGeneratorInterface $urlGenerator, ManagerRegistry $doctrine)
    {
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
    }

    public function prepareResultItem(PrepareResultItemEvent $event): void
    {
        if ($event->getResultItem()->getEntityName() !== EmailUser::class) {
            return;
        }

        $resultItem = $event->getResultItem();

        $emailId = $this->doctrine->getManagerForClass(EmailUser::class)
            ->find(EmailUser::class, $resultItem->getId())
            ->getEmail()
            ->getId();

        $resultItem->setRecordId($emailId);
        $resultItem->setEntityName(Email::class);
        $resultItem->setRecordUrl($this->urlGenerator->generate(
            'oro_email_thread_view',
            ['id' => $emailId],
            UrlGeneratorInterface::ABSOLUTE_URL
        ));
    }
}
