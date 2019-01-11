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
 * @group schema
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
            // Excludes entity from mapping check which causes error while updating from old dump
            // (commerce-crm-ee_1.0.0.pgsql.sql.gz). The situation Should be handled in the BAP-18113 task.
            $temporaryExclude = 'Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance';

            if (isset($validateMapping[$temporaryExclude])) {
                unset($validateMapping[$temporaryExclude]);
            }

            if ($validateMapping) {
                $errors = call_user_func_array('array_merge', $validateMapping);
                $this->fail(implode("\n", $errors));
            }
        }
    }

    /**
     * @see \Oro\Bundle\EntityExtendBundle\Command\UpdateSchemaCommand::execute
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
                'ALTER TABLE oro_website_search_text ADD CONSTRAINT FK_B178C6FC126F525E FOREIGN KEY (item_id) ' .
                'REFERENCES oro_website_search_item (id)',
                // https://github.com/doctrine/dbal-documentation/blob/master/en/reference/known-vendor-issues.rst
                'ALTER TABLE oro_audit_field CHANGE old_datetimetz old_datetimetz DATETIME DEFAULT NULL, ' .
                'CHANGE new_datetimetz new_datetimetz DATETIME DEFAULT NULL',
                // MySQL is blind for partial indices
                'DROP INDEX idx_oro_product_featured ON oro_product',
                'DROP INDEX idx_oro_product_new_arrival ON oro_product',
                'DROP INDEX opportunity_created_idx ON orocrm_sales_opportunity',
                'DROP INDEX lead_created_idx ON orocrm_sales_lead',
                'DROP INDEX request_create_idx ON orocrm_contactus_request',
                'DROP INDEX mageorder_created_idx ON orocrm_magento_order',
                'DROP INDEX magecustomer_rev_name_idx ON orocrm_magento_customer',
                'DROP INDEX magecart_updated_idx ON orocrm_magento_cart',
                'CREATE INDEX idx_oro_product_featured ON oro_product (is_featured)',
                'CREATE INDEX idx_oro_product_new_arrival ON oro_product (is_new_arrival)',
                'CREATE INDEX opportunity_created_idx ON orocrm_sales_opportunity (created_at, id)',
                'CREATE INDEX lead_created_idx ON orocrm_sales_lead (createdAt, id)',
                'CREATE INDEX request_create_idx ON orocrm_contactus_request (created_at, id)',
                'CREATE INDEX mageorder_created_idx ON orocrm_magento_order (created_at, id)',
                'CREATE INDEX magecustomer_rev_name_idx ON orocrm_magento_customer (last_name, first_name, id)',
                'CREATE INDEX magecart_updated_idx ON orocrm_magento_cart (updatedAt, id)'
            ],
        ];

        /** @var EntityManager $em */
        foreach ($registry->getManagers() as $em) {
            $schemaTool = new SchemaTool($em);
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();

            $queries = $schemaTool->getUpdateSchemaSql($allMetadata, true);

            $platform = $em->getConnection()->getDatabasePlatform()->getName();
            if (array_key_exists($platform, $ignoredQueries)) {
                $queries = array_diff($queries, $ignoredQueries[$platform]);
            }

            $this->assertEmpty($queries, implode("\n", $queries));
        }
    }
}
