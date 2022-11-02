<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListIdProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestActivityList;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestActivityProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestFeatureAwareActivityProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestOrganization;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestUser;
use Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDataHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActivityListManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityListManager */
    private $activityListManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $activityListIdProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $commentManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var WorkflowDataHelper */
    private $workflowHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->config = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->provider = $this->createMock(ActivityListChainProvider::class);
        $this->activityListIdProvider = $this->createMock(ActivityListIdProvider::class);
        $this->commentManager = $this->createMock(CommentApiManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->workflowHelper = $this->createMock(WorkflowDataHelper::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->htmlTagHelper->expects($this->any())
            ->method('purify')
            ->willReturnArgument(0);

        $this->activityListManager = new ActivityListManager(
            $this->authorizationChecker,
            $this->entityNameResolver,
            $this->config,
            $this->provider,
            $this->activityListIdProvider,
            $this->commentManager,
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->workflowHelper,
            $this->htmlTagHelper
        );
    }

    public function testGetRepository()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ActivityList::class);
        $this->activityListManager->getRepository();
    }

    public function testGetNonExistItem()
    {
        $repo = $this->createMock(ActivityListRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with(12)
            ->willReturn(null);
        $this->assertNull($this->activityListManager->getItem(12));
    }

    public function testGetEntityViewModelWhenActivityIsAbsent()
    {
        $activityList = $this->createMock(ActivityList::class);
        $activityList->expects(self::any())
            ->method('getRelatedActivityClass');
        $activityList->expects(self::once())
            ->method('getRelatedActivityId');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturn(null);
        $entityProvider = $this->createMock(TestFeatureAwareActivityProvider::class);
        $entityProvider->expects(self::once())
            ->method('isFeaturesEnabled')
            ->willReturn(true);

        $this->provider->expects(self::once())
            ->method('getProviderForEntity')
            ->willReturn($entityProvider);

        $result = $this->activityListManager->getEntityViewModel($activityList);

        self::assertNull($result);
    }

    public function testGetItem()
    {
        $testItem = new TestActivityList();
        $testItem->setId(105);
        $owner = new TestUser();
        $owner->setId(15);
        $updatedBy = new TestUser();
        $updatedBy->setId(142);
        $organization = new TestOrganization();
        $organization->setId(584);
        $testItem->setOwner($owner);
        $testItem->setUpdatedBy($updatedBy);
        $testItem->setOrganization($organization);
        $testItem->setCreatedAt(new \DateTime('2012-01-01', new \DateTimeZone('UTC')));
        $testItem->setUpdatedAt(new \DateTime('2014-01-01', new \DateTimeZone('UTC')));
        $testItem->setVerb(ActivityList::VERB_UPDATE);
        $testItem->setSubject('test_subject');
        $testItem->setDescription('test_description');
        $testItem->setRelatedActivityClass('Acme\TestBundle\Entity\TestEntity');
        $testItem->setRelatedActivityId(127);

        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(function ($user) {
                if ($user->getId() === 15) {
                    return 'Owner_String';
                }

                return 'Editor_String';
            });

        $repo = $this->createMock(ActivityListRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repo);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturn($testItem);
        $repo->expects($this->once())
            ->method('find')
            ->with(105)
            ->willReturn($testItem);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $provider = new TestActivityProvider();
        $this->provider->expects($this->once())
            ->method('getProviderForEntity')
            ->willReturn($provider);

        $this->assertEquals(
            [
                'id'                   => 105,
                'owner'                => 'Owner_String',
                'owner_id'             => 15,
                'editor'               => 'Editor_String',
                'editor_id'            => 142,
                'verb'                 => 'update',
                'subject'              => 'test_subject',
                'description'          => 'test_description',
                'data'                 => ['test_data'],
                'relatedActivityClass' => 'Acme\TestBundle\Entity\TestEntity',
                'relatedActivityId'    => 127,
                'createdAt'            => '2012-01-01T00:00:00+00:00',
                'updatedAt'            => '2014-01-01T00:00:00+00:00',
                'editable'             => true,
                'removable'            => true,
                'commentCount'         => '',
                'commentable'          => '',
                'targetEntityData'     => [],
                'is_head'              => false,
                'workflowsData'        => null,
                'routes'               => [
                    'delete' => 'test_delete_route'
                ]
            ],
            $this->activityListManager->getItem(105)
        );
    }
}
