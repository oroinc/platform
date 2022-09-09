<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\MaterializedView;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait MaterializedViewsAwareTestTrait
{
    protected static function deleteAllMaterializedViews(ContainerInterface $container): void
    {
        $materializedViewEntities = $container
            ->get('doctrine')
            ->getRepository(MaterializedViewEntity::class)
            ->findAll();

        $materializedViewManager = $container->get('oro_platform.materialized_view.manager');
        foreach ($materializedViewEntities as $materializedViewEntity) {
            $materializedViewManager->delete($materializedViewEntity->getName());
        }
    }

    protected static function getMaterializedViewInfo(ContainerInterface $container, string $name): ?array
    {
        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection();

        $result = $connection->executeQuery(
            'SELECT matviewname, definition, ispopulated FROM pg_matviews WHERE matviewname = :name',
            ['name' => $name],
            ['name' => Types::STRING]
        );

        $rows = $result->fetchAllAssociative();

        return $rows[0] ?? null;
    }

    protected static function generateMaterializedViewRandomName(): string
    {
        return substr(str_replace(['-', '.'], '', uniqid('mat_view_' . UUIDGenerator::v4(), true)), 0, 63);
    }
}
