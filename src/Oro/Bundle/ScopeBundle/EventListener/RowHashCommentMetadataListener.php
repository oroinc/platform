<?php

namespace Oro\Bundle\ScopeBundle\EventListener;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRowHashManager;

/**
 * Added comment metadata to row_hash column
 */
class RowHashCommentMetadataListener
{
    private AddCommentToRowHashManager $manager;

    public function __construct(AddCommentToRowHashManager $manager)
    {
        $this->manager = $manager;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        if (!$this->isPlatformSupport($event->getEntityManager())) {
            return;
        }

        /** @var ClassMetadata $metadata */
        $metadata = $event->getClassMetadata();
        if ($metadata->getTableName() !== 'oro_scope') {
            return;
        }

        $relations = $this->manager->getRelations();
        $metadata->setAttributeOverride(
            'rowHash',
            array_merge(
                $metadata->fieldMappings['rowHash'],
                ['options' => ['comment' => $relations]]
            )
        );
    }

    private function isPlatformSupport(EntityManagerInterface $em): bool
    {
        $platform = $em->getConnection()->getDatabasePlatform();

        return ($platform instanceof PostgreSqlPlatform || $platform instanceof MySqlPlatform);
    }
}
