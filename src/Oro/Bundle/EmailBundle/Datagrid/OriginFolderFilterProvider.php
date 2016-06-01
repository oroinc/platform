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
     * Get origins list choices.
     *
     * @param bool $extended
     * @return array
     */
    public function getListTypeChoices($extended = false)
    {
        $results = [];
        $results = $this->preparePersonalOrigin($results, $extended);
        $results = $this->prepareMailboxOrigins($results, $extended);

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
     * @param $extended
     *
     * @return array
     */
    protected function preparePersonalOrigin($results, $extended)
    {
        $origins = $this->getOrigins();
        foreach ($origins as $origin) {
            $folders = $origin->getFolders();
            $mailbox = $origin->getMailboxName();
            $folders = $this->filterFolders($folders->toArray());
            if (count($folders) > 0) {
                $results[$mailbox] = [
                    'id' => $origin->getId(),
                    'active' => $origin->isActive(),
                ];
                $i=1;
                foreach ($folders as $folder) {
                    if ($extended) {
                        $results[$mailbox]['folder'][$i]['id'] = $folder->getId();
                        $results[$mailbox]['folder'][$i]['syncEnabled'] = $folder->isSyncEnabled();
                        $results[$mailbox]['folder'][$i]['fullName'] = str_replace('@', '\@', $folder->getFullName());
                        $i++;
                    } else {
                        $results[$mailbox]['folder'][$folder->getId()] = str_replace('@', '\@', $folder->getFullName());
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @param $results
     * @param $extended
     *
     * @return mixed
     */
    protected function prepareMailboxOrigins($results, $extended)
    {
        $systemMailboxes = $this->getMailboxes();
        foreach ($systemMailboxes as $mailbox) {
            $origin = $mailbox->getOrigin();
            $folders = $origin->getFolders();
            $mailbox = $mailbox->getLabel();
            $folders = $this->filterFolders($folders->toArray());
            if (count($folders) > 0) {
                $results[$mailbox] = [
                    'id' => $origin->getId(),
                    'active' => $origin->isActive(),
                ];
                $i=1;
                foreach ($folders as $folder) {
                    if ($extended) {
                        $results[$mailbox]['folder'][$i]['id'] = $folder->getId();
                        $results[$mailbox]['folder'][$i]['syncEnabled'] = $folder->isSyncEnabled();
                        $results[$mailbox]['folder'][$i]['fullName'] = str_replace('@', '\@', $folder->getFullName());
                        $i++;
                    } else {
                        $results[$mailbox]['folder'][$folder->getId()] = str_replace('@', '\@', $folder->getFullName());
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @param $folders array
     * @return EmailFolder[]
     */
    private function filterFolders(array $folders)
    {
        $folders = array_filter(
            $folders,
            function (EmailFolder $item) {
                return $item->isSyncEnabled();
            }
        );

        return $folders;
    }
}
