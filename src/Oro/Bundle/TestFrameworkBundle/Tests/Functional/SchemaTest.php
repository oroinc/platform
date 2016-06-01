<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class SchemaTest extends WebTestCase
{
    use SchemaTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testMapping()
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityManager $em */
        foreach ($registry->getManagers() as $em) {
            $validator = new SchemaValidator($em);

            $validateMapping = $validator->validateMapping();
            if ($validateMapping) {
                $errors = call_user_func_array('array_merge', $validateMapping);
                $this->fail(implode("\n", $errors));
            }
        }
    }

    /**
     * @see Oro\Bundle\EntityExtendBundle\Command\UpdateSchemaCommand::execute
     */
    public function testSchema()
    {
        $this->overrideRemoveNamespacedAssets();
        $this->overrideSchemaDiff();

        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');

        $ignoredQueries = [
            DatabasePlatformInterface::DATABASE_MYSQL => [
                // reference from myisam table
                'ALTER TABLE oro_search_index_text ADD CONSTRAINT FK_A0243539126F525E FOREIGN KEY (item_id) ' .
                'REFERENCES oro_search_item (id)',
                // https://github.com/doctrine/dbal-documentation/blob/master/en/reference/known-vendor-issues.rst
                'ALTER TABLE oro_audit_field CHANGE old_datetimetz old_datetimetz DATETIME DEFAULT NULL, ' .
                'CHANGE new_datetimetz new_datetimetz DATETIME DEFAULT NULL',
            ],
        ];

        /** @var EntityManager $em */
        foreach ($registry->getManagers() as $em) {
            $schemaTool = new SchemaTool($em);
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();

            $queries = $schemaTool->getUpdateSchemaSql($allMetadata, true);

            $platform = $em->getConnection()->getDatabasePlatform()->getName();
            if (array_key_exists($platform, $ignoredQueries)) {
                $queries = array_filter(
                    $queries,
                    function ($query) use ($ignoredQueries, $platform) {
                        return !in_array($query, $ignoredQueries[$platform], true);
                    }
                );
            }

            $this->assertEmpty($queries, implode("\n", $queries));
        }
    }
}
