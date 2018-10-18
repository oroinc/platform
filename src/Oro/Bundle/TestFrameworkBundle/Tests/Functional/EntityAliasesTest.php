<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityAliasesTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldLoadAllEntityAliasesWithoutErrors()
    {
        /** @var EntityAliasLoader $entityAliasLoader */
        $entityAliasLoader = self::getContainer()->get('oro_test.entity_alias_loader');
        $entityAliasLoader->load(new EntityAliasStorage());
    }
}
