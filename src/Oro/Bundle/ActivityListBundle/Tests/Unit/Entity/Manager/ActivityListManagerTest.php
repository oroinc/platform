<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;
use Oro\Bundle\ActivityListBundle\Helper\ActivityInheritanceTargetsHelper;
use Oro\Bundle\ActivityListBundle\Helper\ActivityListAclCriteriaHelper;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture\TestActivityList;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture\TestOrganization;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture\TestUser;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture\TestActivityProvider;
use Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDataHelper;

class ActivityListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListManager */
    protected $activityListManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityListFilterHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $commentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $inheritanceHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var WorkflowDataHelper */
    protected $workflowHelper;

    public function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->config = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->provider = $this->createMock(ActivityListChainProvider::class);
        $this->activityListFilterHelper = $this->createMock(ActivityListFilterHelper::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->commentManager = $this->createMock(CommentApiManager::class);
        $this->aclHelper = $this->createMock(ActivityListAclCriteriaHelper::class);
        $this->inheritanceHelper = $this->createMock(ActivityInheritanceTargetsHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->workflowHelper = $this->createMock(WorkflowDataHelper::class);

        $this->activityListManager = new ActivityListManager(
            $this->authorizationChecker,
            $this->entityNameResolver,
            $this->config,
            $this->provider,
            $this->activityListFilterHelper,
            $this->commentManager,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->inheritanceHelper,
            $this->eventDispatcher,
            $this->workflowHelper
        );
    }

    public function testGetRepository()
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroActivityListBundle:ActivityList');
        $this->activityListManager->getRepository();
    }

    public function testGetNonExistItem()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $repo->expects($this->once())->method('find')->with(12)->willReturn(null);
        $this->assertNull($this->activityListManager->getItem(12));
    }

    public function testGetItem()
    {
        $testItem = new TestActivityList();
        $testItem->setId(105);
        $owner = new TestUser();
        $owner->setId(15);
        $editor = new TestUser();
        $editor->setId(142);
        $organization = new TestOrganization();
        $organization->setId(584);
        $testItem->setOwner($owner);
        $testItem->setEditor($editor);
        $testItem->setOrganization($organization);
        $testItem->setCreatedAt(new \DateTime('2012-01-01', new \DateTimeZone('UTC')));
        $testItem->setUpdatedAt(new \DateTime('2014-01-01', new \DateTimeZone('UTC')));
        $testItem->setVerb(ActivityList::VERB_UPDATE);
        $testItem->setSubject('test_subject');
        $testItem->setDescription('test_description');
        $testItem->setRelatedActivityClass('Acme\TestBundle\Entity\TestEntity');
        $testItem->setRelatedActivityId(127);

        $this->entityNameResolver->expects($this->any())->method('getName')
            ->willReturnCallback(
                function ($user) {
                    if ($user->getId() === 15) {
                        return 'Owner_String';
                    }

                    return 'Editor_String';
                }
            );

        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $repo->expects($this->once())->method('find')->with(105)->willReturn($testItem);

        $this->authorizationChecker->expects($this->any())->method('isGranted')->willReturn(true);

        $provider = new TestActivityProvider();
        $this->provider->expects($this->once())->method('getProviderForEntity')->willReturn($provider);

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
                'workflowsData'        => null
            ],
            $this->activityListManager->getItem(105)
        );
    }

    public function testGetGroupedEntitiesEmpty()
    {
        $this->provider
            ->expects($this->once())
            ->method('getProviderForEntity')
            ->willReturn($this->returnValue(new TestActivityProvider()));
        $this->assertCount(0, $this->activityListManager->getGroupedEntities(new \stdClass(), '', '', 0, []));
    }

    protected function mockEmailActivityListProvider()
    {
        $emailActivityListProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
        ->disableOriginalConstructor()->getMock();

        $emailActivityListProvider->expects($this->once())->method('getActivityClass')->willReturn('ActivityClass');
        $emailActivityListProvider->expects($this->once())->method('getAclClass')->willReturn('AclClass');

        return $emailActivityListProvider;
    }
}
