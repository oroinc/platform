<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadImportExportResultData extends AbstractFixture
{
    use UserUtilityTrait;

    public const EXPIRED_IMPORT_EXPORT_RESULT = 'expiredImportExportResult';

    /**
     * @var array
     */
    private $importExportResults = [
        self::EXPIRED_IMPORT_EXPORT_RESULT => [
            'jobId' =>  42,
            'expired' => true
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);

        foreach ($this->importExportResults as $reference => $data) {
            $entity = new ImportExportResult();
            $entity->setJobId($data['jobId']);
            $entity->setOwner($user);
            $entity->setOrganization($user->getOrganization());
            $entity->setExpired($data['expired']);
            $entity->setType('export');
            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
