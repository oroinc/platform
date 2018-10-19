<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailActivityListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EmailActivityListProvider */
    protected $emailActivityListProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineRegistryLink;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $mailboxProcessStorageLink;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $activityAssociationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $commentAssociationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->router = $this->createMock(Router::class);
        $this->configManager  = $this->createMock(ConfigManager::class);
        $emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->doctrineRegistryLink = $this->createMock(ServiceLink::class);
        $this->mailboxProcessStorageLink = $this->createMock(ServiceLink::class);
        $this->activityAssociationHelper = $this->createMock(ActivityAssociationHelper::class);
        $this->commentAssociationHelper = $this->createMock(CommentAssociationHelper::class);
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isFeatureEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailActivityListProvider = new EmailActivityListProvider(
            $this->doctrineHelper,
            $this->doctrineRegistryLink,
            $this->entityNameResolver,
            $this->router,
            $this->configManager,
            $emailThreadProvider,
            $htmlTagHelper,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->mailboxProcessStorageLink,
            $this->activityAssociationHelper,
            $this->commentAssociationHelper
        );
    }

    public function testGetActivityOwners()
    {
        $organization = new Organization();
        $organization->setName('Org');
        $user = new User();
        $user->setUsername('test');
        $user->setOrganization($organization);
        $emailUser = new EmailUser();
        $emailUser->setOrganization($organization);
        $emailUser->setOwner($user);
        $owners = [$emailUser];

        $fromEmailAddress = new EmailAddress();
        $fromEmailAddress->setOwner($user);
        $email = new Email();
        $email->setFromEmailAddress($fromEmailAddress);
        $email->addEmailUser($emailUser);

        /** @var ActivityList $activityListMock */
        $activityListMock = $this->createMock(ActivityList::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrineRegistryLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn($owners);

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($email, $activityListMock);

        $this->assertCount(1, $activityOwnerArray);
        $owner = reset($activityOwnerArray);
        $this->assertEquals($organization->getName(), $owner->getOrganization()->getName());
        $this->assertEquals($user->getUsername(), $owner->getUser()->getUsername());
    }

    public function testGetActivityOwnersSeveralOrganizations()
    {
        $organization1 = new Organization();
        $organization1->setName('Org1');

        $organization2 = new Organization();
        $organization2->setName('Org2');

        $user = new User();
        $user->setUsername('test');
        $user->setOrganization($organization1);
        $emailUser = new EmailUser();
        $emailUser->setOrganization($organization2);
        $emailUser->setOwner($user);

        $fromEmailAddress = new EmailAddress();
        $fromEmailAddress->setOwner($user);
        $email = new Email();
        $email->setFromEmailAddress($fromEmailAddress);
        $email->addEmailUser($emailUser);

        /** @var ActivityList $activityListMock */
        $activityListMock = $this->createMock(ActivityList::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrineRegistryLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findBy')
            ->with([
                'email' => $email,
                'organization' => [
                    $organization1,
                    $organization2
                ]
            ])
            ->willReturn([$emailUser]);

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($email, $activityListMock);

        $this->assertCount(1, $activityOwnerArray);
        $owner = reset($activityOwnerArray);
        $this->assertEquals($organization2->getName(), $owner->getOrganization()->getName());
        $this->assertEquals($user->getUsername(), $owner->getUser()->getUsername());
    }

    public function testFeatureToggleable()
    {
        $this->assertInstanceOf(FeatureToggleableInterface::class, $this->emailActivityListProvider);

        $this->emailActivityListProvider->setFeatureChecker($this->featureChecker);
        $this->emailActivityListProvider->addFeature('email');

        $mock = $this->getMockBuilder(FeatureCheckerHolderTrait::class)->setMethods(['isFeaturesEnabled'])
            ->getMockForTrait();

        $mock->expects($this->any())
            ->method('isFeaturesEnabled')
            ->will($this->returnValue(true));

        $this->assertTrue($mock->isFeaturesEnabled());
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param EntityMetadata $metadata
     * @param bool $expected
     */
    public function testGetData(EntityMetadata $metadata = null, $expected)
    {
        /** @var ActivityList|\PHPUnit\Framework\MockObject\MockObject $activityList */
        $activityList = $this->createMock(ActivityList::class);
        $activityList->expects($this->once())
            ->method('getRelatedActivityId')
            ->willReturn(42);

        $user = new User();

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($user)
            ->willReturn('test name');

        $emailAddress = $this->getEntity(EmailAddress::class, ['owner' => $user]);
        $email = $this->getEntity(
            Email::class,
            [
                'id' => 42,
                'subject' => 'test subject',
                'sentAt' => new \DateTime('2018-03-23T11:43:15+00:00'),
                'fromEmailAddress' => $emailAddress,
            ]
        );

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($email);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrineRegistryLink->expects($this->once())
            ->method('getService')
            ->willReturn($em);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with(User::class)
            ->willReturn($metadata);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturn('test/user/link/1');

        $this->assertEquals(
            [
                'ownerName' => 'test name',
                'ownerLink' => $expected ? 'test/user/link/1' : null,
                'entityId' => 42,
                'headOwnerName' => 'test name',
                'headSubject' => 'test subject',
                'headSentAt' => '2018-03-23T11:43:15+00:00',
                'isHead' => false,
                'treadId' => null,
            ],
            $this->emailActivityListProvider->getData($activityList)
        );
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        $metadata = new EntityMetadata(User::class);
        $metadata->routes = ['view' => 'test_route'];

        return [
            'with owner class metadata' => [
                'metadata' => $metadata,
                'ownerLink' => 'test_route'
            ],
            'without owner class metadata' => [
                'metadata' => null,
                'ownerLink' => null
            ]
        ];
    }
}
