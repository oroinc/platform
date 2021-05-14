<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TagBundle\Entity\Repository\TagRepository;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable as TaggableStub;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagManagerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_TAG_NAME     = 'testName';
    private const TEST_NEW_TAG_NAME = 'testAnotherName';
    private const TEST_TAG_ID       = 3333;
    private const TEST_ENTITY_NAME  = 'test name';
    private const TEST_RECORD_ID    = 1;
    private const TEST_CREATED_ID   = 22;
    private const TEST_USER_ID      = 'someID';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Router|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var User|\PHPUnit\Framework\MockObject\MockObject */
    private $user;

    /** @var TagManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->router = $this->createMock(Router::class);

        $this->user = $this->getUser(self::TEST_USER_ID);

        $this->manager = new TagManager(
            $this->em,
            Tag::class,
            Tagging::class,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->router
        );
    }

    /**
     * @return User|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getUser(string $userId)
    {
        $result = $this->createMock(User::class);
        $result->expects($this->any())
            ->method('getId')
            ->willReturn($userId);

        return $result;
    }

    public function testAddTags()
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $testTags = [new Tag(self::TEST_TAG_NAME)];

        $collection = $this->createMock(ArrayCollection::class);
        $collection->expects($this->once())
            ->method('add');

        $resource = $this->createMock(Taggable::class);
        $resource->expects($this->once())
            ->method('getTags')
            ->willReturn($collection);

        $this->manager->addTags($testTags, $resource);
    }

    /**
     * @dataProvider getTagNames
     */
    public function testLoadOrCreateTags(array $names, bool $shouldWorkWithDB, int $resultCount, array $tagsFromDB)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $repo = $this->createMock(EntityRepository::class);
        $this->em->expects($this->exactly((int) $shouldWorkWithDB))
            ->method('getRepository')
            ->willReturn($repo);

        $repo->expects($this->exactly((int) $shouldWorkWithDB))
            ->method('findBy')
            ->willReturn($tagsFromDB);

        $result = $this->manager->loadOrCreateTags($names);

        $this->assertCount($resultCount, $result);
        if ($shouldWorkWithDB) {
            $this->assertContainsOnlyInstancesOf(Tag::class, $result);
        }
    }

    public function getTagNames(): array
    {
        return [
            'with empty tag name will return empty array' => [
                'names' => [],
                'shouldWorkWithDB' => false,
                'resultCount' => 0,
                []
            ],
            'with 1 tag from DB and 1 new tag' => [
                'names' => [self::TEST_TAG_NAME, self::TEST_NEW_TAG_NAME],
                'shouldWorkWithDB' => true,
                'resultCount' => 2,
                [new Tag(self::TEST_TAG_NAME)]
            ]
        ];
    }

    public function tagIdsProvider(): array
    {
        $tag = $this->createMock(Tag::class);
        $tag->expects($this->once())
            ->method('getId')
            ->willReturn(self::TEST_TAG_ID);

        return [
            'null value should pass as array' => [
                'tagIds'           => null,
                'entityName'       => self::TEST_ENTITY_NAME,
                'recordId'         => self::TEST_RECORD_ID,
                'createdBy'        => self::TEST_CREATED_ID,
                'expectedCallArg'  => []
            ],
            'some ids data ' => [
                'tagIds'           => [self::TEST_TAG_ID],
                'entityName'       => self::TEST_ENTITY_NAME,
                'recordId'         => self::TEST_RECORD_ID,
                'createdBy'        => self::TEST_CREATED_ID,
                'expectedCallArg'  => [self::TEST_TAG_ID]
            ],
            'some array collection' => [
                'tagIds'            => new ArrayCollection([$tag]),
                'entityName'        => self::TEST_ENTITY_NAME,
                'recordId'          => self::TEST_RECORD_ID,
                'createdBy'         => self::TEST_CREATED_ID,
                'expectedCallArg'   => [self::TEST_TAG_ID]

            ]
        ];
    }

    public function testLoadTagging()
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $resource = $this->createMock(Taggable::class);
        $repo = $this->createMock(TagRepository::class);
        $repo->expects($this->once())
            ->method('getTags')
            ->willReturn([new Tag(self::TEST_TAG_NAME)]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Tag::class)
            ->willReturn($repo);

        $this->manager->loadTagging($resource);
    }

    public function testCompareCallback()
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $tag = new Tag('testName');
        $tagToCompare = new Tag('testName');
        $tagToCompare2 = new Tag('notTheSameName');

        $callback = $this->manager->compareCallback($tag);

        $this->assertTrue($callback(1, $tagToCompare));
        $this->assertFalse($callback(1, $tagToCompare2));
    }

    public function testGetPreparedArrayFromDb()
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $resource = new TaggableStub(['id' => 1]);
        $tagging = $this->createMock(Tagging::class);

        $tag1 = $this->createMock(Tag::class);
        $tag1->expects($this->once())
            ->method('getName')
            ->willReturn('test name 1');
        $tag1->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $tag1->expects($this->once())
            ->method('getTagging')
            ->willReturn(new ArrayCollection([$tagging]));

        $tag2 = $this->createMock(Tag::class);
        $tag2->expects($this->once())
            ->method('getName')
            ->willReturn('test name 2');
        $tag2->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $tag2->expects($this->once())
            ->method('getTagging')
            ->willReturn(new ArrayCollection([$tagging]));

        $user1 = $this->getUser(self::TEST_USER_ID);
        $user2 = $this->getUser('uniqueId2');

        $tagging->expects($this->exactly(2))
            ->method('getOwner')
            ->willReturnOnConsecutiveCalls($user1, $user2);
        $tagging->expects($this->any())
            ->method('getEntityName')
            ->willReturn(get_class($resource));
        $tagging->expects($this->any())
            ->method('getRecordId')
            ->willReturn(1);

        $this->user->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(self::TEST_USER_ID);

        $this->router->expects($this->exactly(2))
            ->method('generate');

        $repo = $this->createMock(TagRepository::class);
        $repo->expects($this->once())
            ->method('getTags')
            ->willReturn([$tag1, $tag2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Tag::class)
            ->willReturn($repo);

        $result = $this->manager->getPreparedArray($resource);

        $this->assertCount(2, $result);

        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('owner', $result[0]);

        $this->assertFalse($result[1]['owner']);
        $this->assertTrue($result[0]['owner']);
    }

    public function testGetPreparedArrayFromArray()
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $resource = new TaggableStub(['id' => 1]);

        $this->user->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(self::TEST_USER_ID);

        $this->router->expects($this->once())
            ->method('generate');

        $this->manager->getPreparedArray($resource, $this->tagForPreparing());
    }

    public function testGetTagsByEntityIdsWhenNoUser()
    {
        $entityClass = \stdClass::class;
        $ids = [42];
        $this->em->expects($this->never())
            ->method('getRepository');
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        self::assertEmpty($this->manager->getTagsByEntityIds($entityClass, $ids));
    }

    /**
     * @return ArrayCollection|Tag[]
     */
    private function tagForPreparing()
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);
        $tagging = $this->createMock(Tagging::class);

        $tag1->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('test name 1');
        $tag1->expects($this->any())
            ->method('getId')
            ->willReturn(null);
        $tag1->expects($this->once())
            ->method('getTagging')
            ->willReturn(new ArrayCollection([$tagging]));

        $tag2->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $tag2->expects($this->once())
            ->method('getName')
            ->willReturn('test name 2');
        $tag2->expects($this->once())
            ->method('getTagging')
            ->willReturn(new ArrayCollection([$tagging]));

        $user1 = $this->getUser(self::TEST_USER_ID);
        $user2 = $this->getUser('uniqueId2');

        $tagging->expects($this->exactly(2))
            ->method('getOwner')
            ->willReturnOnConsecutiveCalls($user1, $user2);
        $tagging->expects($this->any())
            ->method('getEntityName')
            ->willReturn(TaggableStub::class);
        $tagging->expects($this->any())
            ->method('getRecordId')
            ->willReturn(1);

        return new ArrayCollection([$tag1, $tag2]);
    }
}
