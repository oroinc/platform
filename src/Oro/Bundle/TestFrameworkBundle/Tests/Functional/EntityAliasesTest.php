<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Provider\TestEntityAliasResolver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityAliasesTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testAliasErrors()
    {
        /** @var TestEntityAliasResolver $entityAliasResolver */
        $entityAliasResolver = self::getContainer()->get('oro_test.entity_alias_resolver');
        $entityAliasResolver->clearCache();
        $entityAliasResolver->getAll();

        self::assertEmpty($entityAliasResolver->popLogs());
    }
}
