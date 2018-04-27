<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class OriginFolderFilterProvider
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var MailboxNameHelper */
    private $mailboxNameHelper;

    /**
     * @param ManagerRegistry        $doctrine
     * @param TokenAccessorInterface $tokenAccessor
     * @param MailboxNameHelper      $mailboxNameHelper
     */
    public function __construct(
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        MailboxNameHelper $mailboxNameHelper
    ) {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->mailboxNameHelper = $mailboxNameHelper;
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
        return $this->doctrine->getRepository(EmailOrigin::class)
                ->createQueryBuilder('eo')
                ->select('eo, f, m')
                ->leftJoin('eo.folders', 'f')
                ->leftJoin('eo.mailbox', 'm')
                ->andWhere('eo.owner = :owner')
                ->andWhere('eo.organization = :organization')
                ->andWhere('eo.isActive = :isActive')
                ->setParameters([
                    'owner' => $this->tokenAccessor->getUser(),
                    'organization' => $this->tokenAccessor->getOrganization(),
                    'isActive' => true,
                ])
                ->getQuery()
                ->getResult();
    }

    /**
     * @return Mailbox[]
     */
    protected function getMailboxes()
    {
        $repo = $this->doctrine->getRepository(Mailbox::class);

        /** @var Mailbox[] $systemMailboxes */
        return $repo->findAvailableMailboxes(
            $this->tokenAccessor->getUser(),
            $this->tokenAccessor->getOrganization()
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
            $folders = $this->filterFolders($origin->getFolders()->toArray());
            if (count($folders) > 0) {
                $mailbox = $this->mailboxNameHelper->getMailboxName(
                    get_class($origin),
                    $origin->getMailboxName(),
                    null !== $origin->getMailbox() ? $origin->getMailbox()->getLabel() : null
                );
                $results[$mailbox] = [
                    'id' => $origin->getId(),
                    'active' => $origin->isActive(),
                ];
                $i = 1;
                foreach ($folders as $folder) {
                    if ($extended) {
                        $results[$mailbox]['folder'][$i]['id'] = $folder->getId();
                        $results[$mailbox]['folder'][$i]['syncEnabled'] = $folder->isSyncEnabled();
                        $results[$mailbox]['folder'][$i]['fullName'] = str_replace('@', '\@', $folder->getFullName());
                        $i++;
                    } else {
                        $results[$mailbox]['folder'][str_replace('@', '\@', $folder->getFullName())] = $folder->getId();
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
            if (!$origin = $mailbox->getOrigin()) {
                continue;
            }
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
                        $results[$mailbox]['folder'][str_replace('@', '\@', $folder->getFullName())] = $folder->getId();
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
