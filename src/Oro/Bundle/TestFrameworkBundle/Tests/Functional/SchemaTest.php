<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class SchemaTest extends WebTestCase
{
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
            $this->assertEquals([], $validateMapping, implode("\n", $validateMapping));
        }
    }

    /**
     * @see Oro\Bundle\EntityExtendBundle\Command\UpdateSchemaCommand::execute
     */
    public function testSchema()
    {
        class_alias(
            'Oro\Bundle\EntityExtendBundle\Tools\ExtendSchemaUpdateRemoveNamespacedAssets',
            'Doctrine\DBAL\Schema\Visitor\RemoveNamespacedAssets'
        );
        class_alias(
            'Oro\Bundle\MigrationBundle\Migration\Schema\SchemaDiff',
            'Doctrine\DBAL\Schema\SchemaDiff'
        );

        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');

        $ignoredQueries = [
            'mysql' => [
                // reference from myisam table
                'ALTER TABLE oro_search_index_text ADD CONSTRAINT FK_A0243539126F525E FOREIGN KEY (item_id) ' .
                'REFERENCES oro_search_item (id)',
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

            $this->assertEquals([], $queries, implode("\n", $queries));
        }
    }
}
