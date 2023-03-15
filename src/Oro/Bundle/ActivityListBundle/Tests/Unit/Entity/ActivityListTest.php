<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestActivityList;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class ActivityListTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter(): void
    {
        $id = 1;
        $obj = new TestActivityList();
        $obj->setId($id);

        self::assertEquals($id, $obj->getId());
    }

    /**
     * @dataProvider getDateTimeInterfaceDataProvider
     */
    public function testCreatedAtGetter(\DateTimeInterface $date): void
    {
        $obj = (new ActivityList())
            ->setCreatedAt($date);

        self::assertEquals($date, $obj->getCreatedAt());
    }

    /**
     * @dataProvider getDateTimeInterfaceDataProvider
     */
    public function testUpdatedAtGetter(\DateTimeInterface $date): void
    {
        $obj = (new ActivityList())
            ->setUpdatedAt($date);

        self::assertEquals($date, $obj->getUpdatedAt());
    }

    public function getDateTimeInterfaceDataProvider(): array
    {
        return [
            [
                'date' => new \DateTime(),
            ],
            [
                'date' => new \DateTimeImmutable(),
            ]
        ];
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $obj = new ActivityList();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);

        self::assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function getSetDataProvider(): array
    {
        return [
            ['verb', 'testVerb'],
            ['subject', 'testSubject'],
            ['description', 'testDescription'],
            ['relatedActivityClass', 'testRelatedActivityClass'],
            ['relatedActivityId', 123],
            ['updatedAt', new \DateTime('now')],
            ['createdAt', new \DateTime('now')],
            ['owner', new User()],
            ['updatedBy', new User()],
            ['organization', new Organization()]
        ];
    }

    public function testToString(): void
    {
        $obj = new ActivityList();
        $obj->setSubject('test subject');

        self::assertEquals('test subject', (string)$obj);
    }

    public function testActivityOwner(): void
    {
        $user = new User();
        $user->setFirstName('First Name');
        $organization = new Organization();
        $organization->setName('Organization One');
        $activityOwner = new ActivityOwner();
        $activityOwner->setUser($user);
        $activityOwner->setOrganization($organization);
        $activityList = new ActivityList();
        $activityList->addActivityOwner($activityOwner);

        self::assertCount(1, $activityList->getActivityOwners());
        $firstOwner = $activityList->getActivityOwners()->first();
        self::assertEquals('First Name', $firstOwner->getUser()->getFirstName());
        self::assertEquals('Organization One', $firstOwner->getOrganization()->getName());
    }

    public function testIsUpdatedFlags(): void
    {
        $user = $this->createMock(User::class);
        $date = new \DateTime('2012-12-12 12:12:12');
        $activityList = new ActivityList();
        $activityList->setUpdatedBy($user);
        $activityList->setUpdatedAt($date);

        self::assertTrue($activityList->isUpdatedBySet());
        self::assertTrue($activityList->isUpdatedAtSet());
    }

    public function testIsNotUpdatedFlags(): void
    {
        $activityList = new ActivityList();
        $activityList->setUpdatedBy(null);
        $activityList->setUpdatedAt(null);

        self::assertFalse($activityList->isUpdatedBySet());
        self::assertFalse($activityList->isUpdatedAtSet());
    }

    public function testSetSubjectOnLongString(): void
    {
        $activityList = new ActivityList();
        $activityList->setSubject(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur eget elementum velit, ac tempor orci. '
            . 'Cras aliquet massa id dignissim bibendum. Interdum et malesuada fames ac ante ipsum primis in faucibus.'
            .' Aenean ac libero magna. Proin eu tristiqäue est. Donec convallis pretium congue. Nullam sed.'
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur eget elementum velit, ac tempor orci. '
            . 'Cras aliquet massa id dignissim bibendum. Interdum et malesuada fames ac ante ipsum primis in faucibus.'
            . ' Aenean ac libero magna. Proin eu tristiqä',
            $activityList->getSubject()
        );
    }
}
