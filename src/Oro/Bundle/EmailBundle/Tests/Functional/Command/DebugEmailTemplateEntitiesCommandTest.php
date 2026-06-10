<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class DebugEmailTemplateEntitiesCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCommandOutputsEntityList(): void
    {
        $output = self::runCommand('oro:debug:email:template:entities');

        self::assertNotEmpty($output);

        $entities = self::getContainer()->get('oro_email.email_template_entity_provider')->getEntities();
        $entityClasses = array_column($entities, 'name');

        foreach ($entityClasses as $entityClass) {
            self::assertStringContainsString(
                '* ' . $entityClass,
                $output,
                'Entity class ' . $entityClass . ' should have been listed'
            );
        }
    }
}
