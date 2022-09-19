<?php

namespace Oro\Bundle\PlatformBundle\MaterializedView;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\PlatformBundle\MaterializedView\Exception\MaterializedViewAlreadyExistsException;
use Oro\Bundle\PlatformBundle\MaterializedView\Exception\MaterializedViewDoesNotExistException;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedViewSchemaManager;
use Oro\Component\DoctrineUtils\MaterializedView\MaterializedViewByQueryFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Manages CRUD operations for {@see MaterializedView} entity.
 * Proxies corresponding operations to the {@see MaterializedViewSchemaManager}.
 */
class MaterializedViewManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private MaterializedViewByQueryFactory $materializedViewByQueryFactory;

    private MaterializedViewSchemaManager $materializedViewSchemaManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        MaterializedViewByQueryFactory $MaterializedViewByQueryFactory,
        MaterializedViewSchemaManager $materializedViewSchemaManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->materializedViewByQueryFactory = $MaterializedViewByQueryFactory;
        $this->materializedViewSchemaManager = $materializedViewSchemaManager;

        $this->logger = new NullLogger();
    }

    public function createByQuery(Query $query, ?string $name = null, bool $withData = true): MaterializedViewEntity
    {
        $name = $name ?? $this->generateMaterializedViewName();
        $materializedViewEntity = $this->findByName($name);
        if ($materializedViewEntity) {
            throw MaterializedViewAlreadyExistsException::create($name);
        }

        $materializedViewModel = $this->materializedViewByQueryFactory->createByQuery($query, $name, $withData);
        $this->materializedViewSchemaManager->create($materializedViewModel);

        $materializedViewEntity = (new MaterializedViewEntity())
            ->setName($name)
            ->setWithData($withData);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($materializedViewEntity);
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->commit($materializedViewEntity);

        $this->logger->info(
            'Created materialized view {name} (with data: {withData}) from ORM query.',
            [
                'name' => $name,
                'withData' => (int)$withData,
            ]
        );

        return $materializedViewEntity;
    }

    public function delete(string $name): void
    {
        $this->materializedViewSchemaManager->drop($name);

        $materializedViewEntity = $this->findByName($name);
        if ($materializedViewEntity) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->managerRegistry->getManagerForClass(MaterializedViewEntity::class);
            $entityManager->remove($materializedViewEntity);
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->commit($materializedViewEntity);

            $this->logger->info('Deleted materialized view {name}', ['name' => $name]);
        }
    }

    public function refresh(
        string $name,
        bool $concurrently = false,
        bool $withData = true
    ): void {
        $materializedViewEntity = $this->findByName($name);
        if (!$materializedViewEntity) {
            throw MaterializedViewDoesNotExistException::create($name, 'Failed to refresh.');
        }

        $this->materializedViewSchemaManager->refresh($name, $concurrently, $withData);

        $materializedViewEntity
            ->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setWithData($withData);

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(MaterializedViewEntity::class);
        $entityManager->persist($materializedViewEntity);
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->commit($materializedViewEntity);

        $this->logger->info('Refreshed materialized view {name} (with data: {withData})', [
            'name' => $name,
            'concurrently' => (int)$concurrently,
            'withData' => (int)$withData,
        ]);
    }

    public function findByName(string $name): ?MaterializedViewEntity
    {
        return $this->managerRegistry
            ->getRepository(MaterializedViewEntity::class)
            ->findOneBy(['name' => $name]);
    }

    public function getRepository(string $name): MaterializedViewRepository
    {
        return new MaterializedViewRepository($this->getEntityManager(), $name);
    }

    private function getEntityManager(): EntityManager
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(MaterializedViewEntity::class);

        return $entityManager;
    }

    private function generateMaterializedViewName(): string
    {
        // Ensures that materialized view name is unique and starts with a letter.
        return 'm' . str_replace('-', '', UUIDGenerator::v4());
    }
}
