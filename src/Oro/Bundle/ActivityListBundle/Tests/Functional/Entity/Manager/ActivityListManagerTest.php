<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\Entity\Manager;

use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadEmailActivityData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class ActivityListManagerTest extends WebTestCase
{
    private ActivityListManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailActivityData::class, LoadUser::class]);
        $this->manager = self::getContainer()->get(ActivityListManager::class);
    }

    /**
     * @dataProvider emailActivityProvider
     */
    public function testGetEntityViewModelEmailWithNoThread(int $expectedCommentCount, string $activityReference)
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordOrganizationToken(
            $user,
            'key',
            $user->getOrganization(),
            $user->getUserRoles()
        ));

        $result = $this->manager->getEntityViewModel($this->getReference($activityReference));

        self::assertTrue($result['commentable']);
        self::assertEquals($expectedCommentCount, $result['commentCount']);
    }

    public function emailActivityProvider(): array
    {
        return [
            'email_without_thread' => [
                'commentCount' => 1,
                'activityReference' => 'test_activity_list_1'
            ],
            'email_with_thread' => [
                'commentCount' => 3,
                'activityReference' => 'test_activity_list_2'
            ]
        ];
    }
}
