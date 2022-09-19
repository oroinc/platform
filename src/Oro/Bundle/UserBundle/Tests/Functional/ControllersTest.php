<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithAvatars;

class ControllersTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadUserData::class, LoadUsersWithAvatars::class]);
    }

    /**
     * @dataProvider autoCompleteHandlerProvider
     */
    public function testAutoCompleteHandler(bool $active, string $handlerName, string $query): void
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => 'simple_user']);
        $user->setEnabled($active);
        $doctrine->getManager()->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_form_autocomplete_search'),
            [
                'page' => 1,
                'per_page' => 10,
                'name' => $handlerName,
                'query' => $query,
            ]
        );

        $result = $this->client->getResponse();
        $arr = self::getJsonResponseContent($result, 200);
        $this->assertCount((int)$active, $arr['results']);
    }

    public function autoCompleteHandlerProvider(): array
    {
        return [
                'Acl user autocomplete handler active' =>
                [
                    'active' => true,
                    'handler' => 'acl_users',
                    'query' => 'Elley Towards;Oro_Bundle_UserBundle_Entity_User;CREATE;0;'
                ],
                'Acl user autocomplete handler inactive' =>
                [
                    'active' => false,
                    'handler' => 'acl_users',
                    'query' => 'Elley Towards;Oro_Bundle_UserBundle_Entity_User;CREATE;0;'
                ],
                'Organization user autocomplete handler active' =>
                [
                    'active' => true,
                    'handler' => 'organization_users',
                    'query' => 'Elley Towards'
                ],
                'Organization user autocomplete handler inactive' =>
                [
                    'active' => false,
                    'handler' => 'organization_users',
                    'query' => 'Elley Towards'
                ],
        ];
    }
}
