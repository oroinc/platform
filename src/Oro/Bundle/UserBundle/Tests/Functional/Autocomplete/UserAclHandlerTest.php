<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class UserAclHandlerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadUserACLData::class]);
    }

    public function testSearch()
    {
        $this->updateUserSecurityToken(LoadUserACLData::SIMPLE_USER_ROLE_DEEP_WITHOUT_BU);

        $query = ';Oro_Bundle_UserBundle_Entity_User;VIEW;0;';

        /* UserAclHandler $aclHandler */
        $aclHandler = $this->getContainer()->get('oro_user.autocomplete.user.search_acl_handler');
        $data = $aclHandler->search($query, 1, 50);
        static::assertEmpty($data['results']);
    }
}
