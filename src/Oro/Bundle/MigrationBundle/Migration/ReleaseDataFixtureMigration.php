<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\MigrationBundle\Migrations\Schema\v1_0\OroMigrationBundle as MigrationBundleMigration10;

class ReleaseDataFixtureMigration implements Migration
{
    /**
     * @var array
     */
    protected $fixturesData;

    /**
     * @var array
     */
    protected $mappingData;

    /**
     * @var Expr
     */
    protected $expr;

    /**
     * @param array $fixturesData
     * @param array $mappingData
     */
    public function __construct(array $fixturesData, array $mappingData)
    {
        $this->fixturesData = $fixturesData;
        $this->mappingData = $mappingData;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        foreach ($this->fixturesData as $fixtureData) {
            $bundleName = $fixtureData['bundle_name'];
            $dataVersion = $fixtureData['data_version'];
            $demoDataVersion = $fixtureData['demo_data_version'];

            $this->processFixtures($queries, $bundleName, 'main', $dataVersion);
            $this->processFixtures($queries, $bundleName, 'demo', $demoDataVersion);
        }
    }

    /**
     * @param QueryBag $queries
     * @param string $bundle
     * @param string $type
     * @param string $version
     */
    protected function processFixtures(QueryBag $queries, $bundle, $type, $version)
    {
        if ($version && !empty($this->mappingData[$bundle][$type])) {
            $fixturesByVersions = $this->mappingData[$bundle][$type];
            foreach ($fixturesByVersions as $fixtureVersion => $fixtures) {
                if (version_compare($fixtureVersion, $version, '<=')) {
                    foreach ($fixtures as $fixture) {
                        $queries->addPostQuery($this->getInsertFixtureSql($fixture));
                    }
                }
            }
        }
    }

    /**
     * @param string $dataFixture
     * @return string
     */
    protected function getInsertFixtureSql($dataFixture)
    {
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));

        return sprintf(
            'INSERT INTO %s SET %s, %s',
            MigrationBundleMigration10::MIGRATION_DATA_TABLE,
            $this->expr()->eq('class_name', $this->expr()->literal(addslashes($dataFixture))),
            $this->expr()->eq('loaded_at', $this->expr()->literal($createdAt->format('Y-m-d H:i:s')))
        );
    }

    /**
     * @return Expr
     */
    protected function expr()
    {
        if (!$this->expr) {
            $this->expr = new Expr();
        }

        return $this->expr;
    }
}
