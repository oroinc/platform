<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Oro\Bundle\MigrationBundle\Entity\DataMigration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * @group schema
 */
class SchemaTest extends WebTestCase
{
    use SchemaTrait;
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    /**
     * @return EntityManagerInterface[]
     */
    private function getEntityManagers(): array
    {
        $entityManagers = [];
        foreach ($this->getDoctrine()->getManagers() as $manager) {
            if ($manager instanceof EntityManagerInterface) {
                $entityManagers[] = $manager;
            }
        }

        return $entityManagers;
    }

    private function isFreshInstall(): bool
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManagerForClass(DataMigration::class);
        $rows = $em->createQueryBuilder()
            ->from(DataMigration::class, 'e')
            ->select('e.bundle')
            ->groupBy('e.bundle')
            ->having('COUNT(e.id) > 1')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return empty($rows);
    }

    public function testMapping(): void
    {
        foreach ($this->getEntityManagers() as $em) {
            $validator = new SchemaValidator($em);

            $validateMapping = $validator->validateMapping();
            // Excludes entity from mapping check which causes error while updating from old dump
            // (commerce-crm-ee_1.0.0.pgsql.sql.gz). The situation Should be handled in the BAP-18113 task.
            $temporaryExclude = 'Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance';

            if (isset($validateMapping[$temporaryExclude])) {
                unset($validateMapping[$temporaryExclude]);
            }

            if ($validateMapping) {
                $errors = array_merge(...$validateMapping);
                self::fail(implode("\n", $errors));
            }
        }
    }

    /**
     * @see \Oro\Bundle\EntityExtendBundle\Command\UpdateSchemaCommand::execute
     */
    public function testSchema(): void
    {
        $this->overrideRemoveNamespacedAssets();
        $this->overrideSchemaDiff();

        $ignoredQueries = Yaml::parseFile(__DIR__ . DIRECTORY_SEPARATOR . 'ignored_queries.yml');

        foreach ($this->getEntityManagers() as $em) {
            $schemaTool = new SchemaTool($em);
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();

            $queries = $schemaTool->getUpdateSchemaSql($allMetadata, true);

            $platform = $em->getConnection()->getDatabasePlatform()->getName();
            if (array_key_exists($platform, $ignoredQueries['ignored_queries'])) {
                $queries = array_diff($queries, $ignoredQueries['ignored_queries'][$platform]);
            }

            self::assertEmpty($queries, implode("\n", $queries));
        }
    }

    public function testDoctrineSchemaValidateDoesNotDetectIssues(): void
    {
        if (!$this->isFreshInstall()) {
            self::markTestSkipped('Only for fresh install.');
        }

        $commandTester = $this->doExecuteCommand('doctrine:schema:validate');
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, '[OK] The mapping files are correct.');
        $this->assertOutputContains($commandTester, '[OK] The database schema is in sync with the mapping files.');
    }

    public function testDoctrineSchemaUpdateDoesNotDetectDifferences(): void
    {
        if (!$this->isFreshInstall()) {
            self::markTestSkipped('Only for fresh install.');
        }

        $commandTester = $this->doExecuteCommand('doctrine:schema:update', ['--dump-sql' => true]);
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains(
            $commandTester,
            '[OK] Nothing to update - your database is already in sync with the current entity metadata.'
        );
    }
}
