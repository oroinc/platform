<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\ImportExport\Configuration\UserImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;

class UserImportExportConfigurationProviderTest extends TestCase
{
    public function testGet()
    {
        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => User::class,
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_user',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_user',
            ]),
            (new UserImportExportConfigurationProvider())->get()
        );
    }
}
