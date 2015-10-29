<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        foreach ($registry->getManagers() as $em) {
            $validator = new SchemaValidator($em);
            $this->assertEquals([], $validator->validateMapping());
        }
    }

    public function testSchema()
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityManager $em */
        foreach ($registry->getManagers() as $em) {
            $schemaTool = new SchemaTool($em);
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();

            $this->assertEquals([], $schemaTool->getUpdateSchemaSql($allMetadata, true));
        }
    }
}
