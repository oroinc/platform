<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OriginFolderFilterProvider
{
    const EMAIL_ORIGIN = 'OroEmailBundle:EmailOrigin';
    const EMAIL_MAILBOX = 'OroEmailBundle:Mailbox';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var Registry */
    private $doctrine;

    /**
     * @param Registry            $doctrine
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade
    ) {
        $this->doctrine = $doctrine;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Get marketing list types choices.
     *
     * @return array
     */
    public function getListTypeChoices()
    {
        $results = [];
        $results = $this->preparePersonalOrigin($results);
        $results = $this->prepareMailboxOrigins($results);

        return $results;
    }

    /**
     * @return EmailOrigin[]
     */
    protected function getOrigins()
    {
        $criteria = [
            'owner' => $this->securityFacade->getLoggedUser(),
            'organization' => $this->securityFacade->getOrganization(),
            'isActive' => true,
        ];

        return $this->doctrine->getRepository(self::EMAIL_ORIGIN)->findBy($criteria);
    }

    /**
     * @return Mailbox[]
     */
    protected function getMailboxes()
    {
        $repo = $this->doctrine->getRepository(self::EMAIL_MAILBOX);

        /** @var Mailbox[] $systemMailboxes */
        return $repo->findAvailableMailboxes(
            $this->securityFacade->getLoggedUser(),
            $this->securityFacade->getOrganization()
        );
    }

    /**
     * @param $results
     * @return array
     */
    protected function preparePersonalOrigin($results)
    {
        $origins = $this->getOrigins();
        foreach ($origins as $origin) {
            $folders = $origin->getFolders();
            $mailbox = $origin->getMailboxName();
            $folders = $this->filterFolders($folders->toArray());
            if (count($folders) > 0) {
                $results[$mailbox] = [];
                $results[$mailbox]['active'] = $origin->isActive();
                /** @var EmailFolder $folder */
                foreach ($folders as $folder) {
                    $results[$mailbox]['folder'][$folder->getId()] = str_replace('@', '\@', $folder->getFullName());
                }
            }
        }

        return $results;
    }

    /**
     * @param $results
     * @return mixed
     */
    protected function prepareMailboxOrigins($results)
    {
        $systemMailboxes = $this->getMailboxes();
        foreach ($systemMailboxes as $mailbox) {
            $origin = $mailbox->getOrigin();
            $folders = $origin->getFolders();
            $mailboxLabel = $mailbox->getLabel();
            $folders = $this->filterFolders($folders->toArray());
            if (count($folders) > 0) {
                $results[$mailboxLabel] = [];
                $results[$mailboxLabel]['active'] = $origin->isActive();
                /** @var EmailFolder $folder */
                foreach ($folders as $folder) {
                    $results[$mailboxLabel]['folder'][$folder->getId()] = $folder->getFullName();
                }
            }
        }

        return $results;
    }

    /**
     * @param $folders array
     * @return array
     */
    private function filterFolders($folders)
    {
        $folders = array_filter(
            $folders,
            function ($item) {
                /** @var EmailFolder $item */
                return $item->isSyncEnabled();
            }
        );

        return $folders;
    }
}
