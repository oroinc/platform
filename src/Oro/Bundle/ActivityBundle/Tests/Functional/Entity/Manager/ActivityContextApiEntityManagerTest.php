<?php

namespace Oro\Bundle\ActivityBundle\Tests\Functional\Entity\Manager;

use Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadActivityData;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SecurityBundle\Authorization\AuthorizationChecker;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ActivityContextApiEntityManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadActivityData::class,
        ]);

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $entityAliasResolver
            ->method('getPluralAlias')
            ->willReturn('sample-alias-plural');

        self::getContainer()->set('oro_entity.entity_alias_resolver', $entityAliasResolver);

        $authorizationChecker = $this->createMock(AuthorizationChecker::class);
        $authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->getReference('test_activity_target_1'))
            ->willReturnOnConsecutiveCalls(true, false);

        self::getContainer()->set('security.authorization_checker', $authorizationChecker);
    }

    public function testGetActivityContext(): void
    {
        $activity = $this->getReference('test_activity_1');

        $manager = self::getContainer()->get('oro_activity.manager.activity_context.api');

        $result = $manager->getActivityContext(\get_class($activity), $activity->getId());

        $target = $this->getReference('test_activity_target_1');
        $expectedItem = [
            'title' => $target->getId(),
            'activityClassAlias' => 'sample-alias-plural',
            'entityId' => $activity->getId(),
            'targetId' => $target->getId(),
            'targetClassName' => 'Oro_Bundle_TestFrameworkBundle_Entity_TestActivityTarget',
            'icon' => null,
            'link' => null,
        ];

        self::assertEquals([$expectedItem], $result);
    }

    public function testGetActivityContextWhenNotGranted(): void
    {
        $activity = $this->getReference('test_activity_1');

        $manager = self::getContainer()->get('oro_activity.manager.activity_context.api');
        $result = $manager->getActivityContext(\get_class($activity), $activity->getId());

        self::assertEquals([], $result);
    }
}
