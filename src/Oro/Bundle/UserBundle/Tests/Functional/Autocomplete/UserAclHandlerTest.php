<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Autocomplete;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class UserAclHandlerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadUserACLData::class]);
    }

    public function testSearch()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroUserBundle:User');

        $user = $em->getRepository('OroUserBundle:User')
            ->findOneBy(['email' => LoadUserACLData::SIMPLE_USER_ROLE_DEEP_WITHOUT_BU]);
        $organization = $em->getRepository('OroOrganizationBundle:Organization')->find(self::AUTH_ORGANIZATION);

        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $query = ';Oro_Bundle_UserBundle_Entity_User;VIEW;0;';

        /* UserAclHandler $aclHandler */
        $aclHandler = $this->getContainer()->get('oro_user.autocomplete.user.search_acl_handler');
        $data = $aclHandler->search($query, 1, 50);
        static::assertEmpty($data['results']);
    }
}
