<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\MaterializedView;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

class MaterializedViewManagerTest extends WebTestCase
{
    use MaterializedViewsAwareTestTrait;

    private MaterializedViewManager $materializedViewManager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->materializedViewManager = self::getContainer()->get('oro_platform.materialized_view.manager');
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteAllMaterializedViews(self::getContainer());

        parent::tearDownAfterClass();
    }

    public function testCreateByQuery(): void
    {
        $query = $this->getSampleQuery();

        $materializedViewName = self::generateMaterializedViewRandomName();
        $materializedViewEntity = $this->materializedViewManager->createByQuery($query, $materializedViewName);

        self::assertEquals($materializedViewName, $materializedViewEntity->getName());
        self::assertTrue($materializedViewEntity->isWithData());

        self::assertSame($materializedViewEntity, $this->materializedViewManager->findByName($materializedViewName));

        $materializedViewInfo = self::getMaterializedViewInfo(self::getContainer(), $materializedViewName);
        self::assertNotNull($materializedViewInfo);
        // pg_matviews.definition is produced by pg_get_viewdef() which deparses the internal query tree.
        // PostgreSQL >= 16 omits unambiguous table-alias qualifiers (e.g. "o0_.id" becomes "id"),
        // while Doctrine always emits them. Strip alias qualifiers from both sides before comparing
        // so the assertion is Doctrine and PostgreSQL-version-agnostic.
        $normalizeSql = static function (string $sql): string {
            $sql = mb_strtolower(preg_replace('/\s+/', ' ', trim($sql, ' ;')));
            // Remove "<alias>." column qualifiers (word followed by a dot before a non-digit word char)
            return preg_replace('/\b\w+\.(?=[a-z_])/i', '', $sql);
        };
        self::assertEquals(
            $normalizeSql(QueryUtil::getExecutableSql($query)),
            $normalizeSql($materializedViewInfo['definition'])
        );
        self::assertTrue($materializedViewInfo['ispopulated']);
    }

    public function testRefresh(): void
    {
        $query = $this->getSampleQuery();

        $materializedViewName = self::generateMaterializedViewRandomName();
        $materializedViewEntity = $this->materializedViewManager->createByQuery($query, $materializedViewName, false);

        self::assertEquals($materializedViewName, $materializedViewEntity->getName());
        self::assertFalse($materializedViewEntity->isWithData());

        $materializedViewInfo = self::getMaterializedViewInfo(self::getContainer(), $materializedViewName);
        self::assertNotNull($materializedViewInfo);
        self::assertFalse($materializedViewInfo['ispopulated']);

        $this->materializedViewManager->refresh($materializedViewName);
        self::assertTrue($materializedViewEntity->isWithData());

        $materializedViewInfo = self::getMaterializedViewInfo(self::getContainer(), $materializedViewName);
        self::assertNotNull($materializedViewInfo);
        self::assertTrue($materializedViewInfo['ispopulated']);
    }

    public function testDelete(): void
    {
        $query = $this->getSampleQuery();

        $materializedViewName = self::generateMaterializedViewRandomName();
        $materializedViewEntity = $this->materializedViewManager->createByQuery($query, $materializedViewName, false);

        self::assertEquals($materializedViewName, $materializedViewEntity->getName());
        self::assertFalse($materializedViewEntity->isWithData());

        $materializedViewInfo = self::getMaterializedViewInfo(self::getContainer(), $materializedViewName);
        self::assertNotNull($materializedViewInfo);

        $this->materializedViewManager->delete($materializedViewName);

        self::assertNull($this->materializedViewManager->findByName($materializedViewName));

        $materializedViewInfo = self::getMaterializedViewInfo(self::getContainer(), $materializedViewName);
        self::assertNull($materializedViewInfo);
    }

    public function testRowsCount(): void
    {
        $query = $this->getSampleQuery();

        $materializedViewName = self::generateMaterializedViewRandomName();
        $materializedViewEntity = $this->materializedViewManager->createByQuery($query, $materializedViewName, true);

        self::assertEquals($materializedViewName, $materializedViewEntity->getName());
        self::assertTrue($materializedViewEntity->isWithData());

        $repository = $this->materializedViewManager->getRepository($materializedViewName);
        $dbalQueryBuilder = $repository
            ->createQueryBuilder()
            ->select('COUNT(1)');

        $rowsCount = $repository->getRowsCount();
        self::assertEquals($dbalQueryBuilder->execute()->fetchOne(), $rowsCount);

        self::assertEquals(1, $rowsCount);
    }

    private function getSampleQuery(): Query
    {
        /** @var EntityRepository $repository */
        $repository = self::getContainer()
            ->get('doctrine')
            ->getRepository(User::class);

        return $repository->createQueryBuilder('e')->getQuery();
    }
}
