<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadImportExportResultData extends AbstractFixture
{
    use UserUtilityTrait;

    public const EXPIRED_IMPORT_EXPORT_RESULT = 'expiredImportExportResult';
    public const NOT_EXPIRED_IMPORT_EXPORT_RESULT = 'notExpiredImportExportResult';

    /**
     * @var array
     */
    private $importExportResults = [
        self::EXPIRED_IMPORT_EXPORT_RESULT => [
            'jobId' =>  42,
            'expired' => true,
            'entity' => ImportExportResult::class
        ],
        self::NOT_EXPIRED_IMPORT_EXPORT_RESULT => [
            'jobId' =>  123,
            'expired' => false,
            'entity' => ImportExportResult::class
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
            $entity->setEntity($data['entity']);
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
