<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Entity;

use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ImportExportResultTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 1],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', new \DateTime('now'), false],
            ['fileName', "/ACME", false],
            ['jobId', 1, false],
            ['type', 'import_or_export', false],
            ['expired', false, true],
            ['options', ['option1' => 'test'], []],
        ];

        self::assertPropertyAccessors(new ImportExportResult(), $properties);
    }
}
