<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Autocomplete\UserAclHandler;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithAvatars;

class UserAclHandlerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadUserACLData::class, LoadUsersWithAvatars::class]);
    }

    public function testSearch(): void
    {
        $this->updateUserSecurityToken(LoadUserACLData::SIMPLE_USER_ROLE_DEEP_WITHOUT_BU);

        $query = ';Oro_Bundle_UserBundle_Entity_User;VIEW;0;';

        /** @var UserAclHandler $aclHandler */
        $aclHandler = $this->getContainer()->get('oro_user.autocomplete.user.search_acl_handler');
        $data = $aclHandler->search($query, 1, 50);
        self::assertEmpty($data['results']);
    }

    public function testSearchCheckAvatars(): void
    {
        $this->updateUserSecurityToken(LoadUserACLData::SIMPLE_USER_ROLE_SYSTEM);

        $query = 'user2@example.org;Oro_Bundle_UserBundle_Entity_User;VIEW;0;';

        /** @var UserAclHandler $aclHandler */
        $aclHandler = $this->getContainer()->get('oro_user.autocomplete.user.search_acl_handler');
        $searchResults = $aclHandler->search($query, 1, 10);
        self::assertCount(1, $searchResults['results']);

        $user2Result = $searchResults['results'][0];

        $user2 = $this->getReference('user2');
        $user2AvatarFile = $this->getReference(sprintf('user_%d_avatar', $user2->getId()));
        $user2Avatar =  self::getContainer()->get('oro_attachment.manager')
            ->getFilteredImageUrl($user2AvatarFile, 'avatar_xsmall');
        $user2AvatarWebp =  self::getContainer()->get('oro_attachment.manager')
            ->getFilteredImageUrl($user2AvatarFile, 'avatar_xsmall', 'webp');

        self::assertArrayIntersectEquals(
            [
                'avatar' => [
                    'src' => $user2Avatar,
                    'sources' => [
                        [
                            'srcset' => $user2AvatarWebp,
                            'type' => 'image/webp'
                        ]
                    ],
                ],
            ],
            $user2Result
        );
    }
}
