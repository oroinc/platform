<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LanguageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Language(), [
            ['id', 1],
            ['code', 'test_code'],
            ['enabled', true],
            ['installedBuildDate', new \DateTime()],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
    }
}
