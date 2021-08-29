<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\ImportExport\Configuration\UserImportExportConfigurationProvider;

class UserImportExportConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        self::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => User::class,
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_user',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_user',
            ]),
            (new UserImportExportConfigurationProvider())->get()
        );
    }
}
