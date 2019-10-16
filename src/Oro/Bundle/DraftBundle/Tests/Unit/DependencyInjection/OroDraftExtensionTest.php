<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DraftBundle\DependencyInjection\OroDraftExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroDraftExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroDraftExtension());

        $expectedDefinitions = [
            'oro_draft.event_listener_orm.draft_source_listener',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias(): void
    {
        $extension = new OroDraftExtension();

        $this->assertEquals('oro_draft', $extension->getAlias());
    }
}
