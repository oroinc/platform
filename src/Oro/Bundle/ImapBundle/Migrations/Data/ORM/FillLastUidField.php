<?php

namespace Oro\Bundle\ImapBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

/**
 * Fills the `lastUid` field of the ImapEmailFolder entity according to already imported emails.
 */
class FillLastUidField extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $folders = $manager->getRepository(ImapEmailFolder::class)->findAll();
        foreach ($folders as $folder) {
            try {
                $lastUid = $manager->getRepository(ImapEmail::class)
                    ->createQueryBuilder('ie')
                    ->select('ie.uid')
                    ->innerJoin('ie.imapFolder', 'if')
                    ->where('if = :imapFolder')
                    ->setParameter('imapFolder', $folder->getFolder())
                    ->orderBy('ie.uid', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult();
            } catch (NoResultException $e) {
                $lastUid = 0;
            }

            if ((int)$lastUid > 0) {
                $folder->setLastUid((int)$lastUid);
                $manager->persist($folder);
            }
        }

        $manager->flush();
    }
}
