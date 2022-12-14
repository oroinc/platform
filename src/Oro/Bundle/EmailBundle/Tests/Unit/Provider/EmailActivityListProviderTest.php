<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailActivityListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var MailboxProcessStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $mailboxProcessStorage;

    /** @var ActivityAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $activityAssociationHelper;

    /** @var CommentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $commentAssociationHelper;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var EmailActivityListProvider */
    private $emailActivityListProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->configManager  = $this->createMock(ConfigManager::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->mailboxProcessStorage = $this->createMock(MailboxProcessStorage::class);
        $this->activityAssociationHelper = $this->createMock(ActivityAssociationHelper::class);
        $this->commentAssociationHelper = $this->createMock(CommentAssociationHelper::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->emailActivityListProvider = new EmailActivityListProvider(
            $this->doctrineHelper,
            $this->entityNameResolver,
            $this->urlGenerator,
            $this->configManager,
            $emailThreadProvider,
            $htmlTagHelper,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->mailboxProcessStorage,
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

        $activityList = $this->createMock(ActivityList::class);
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn($owners);

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($email, $activityList);

        $this->assertCount(1, $activityOwnerArray);
        $owner = reset($activityOwnerArray);
        $this->assertEquals($organization->getName(), $owner->getOrganization()->getName());
        $this->assertEquals($user->getUsername(), $owner->getUser()->getUsername());
    }

    public function testGetActivityOwnersForPrivateEmail(): void
    {
        $organization = new Organization();
        $organization->setName('Org');
        $user = new User();
        $user->setUsername('test');
        $user->setOrganization($organization);
        $emailUser = new EmailUser();
        $emailUser->setOrganization($organization);
        $emailUser->setOwner($user);
        $emailUser->setIsEmailPrivate(true);

        $fromEmailAddress = new EmailAddress();
        $fromEmailAddress->setOwner($user);
        $email = new Email();
        $email->setFromEmailAddress($fromEmailAddress);
        $email->addEmailUser($emailUser);

        $activityList = $this->createMock(ActivityList::class);
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$emailUser]);

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($email, $activityList);

        $this->assertCount(0, $activityOwnerArray);
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

        $activityList = $this->createMock(ActivityList::class);
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
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

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($email, $activityList);

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

        $featureCheckerHolder = $this->getMockBuilder(FeatureCheckerHolderTrait::class)
            ->onlyMethods(['isFeaturesEnabled'])
            ->getMockForTrait();
        $featureCheckerHolder->expects($this->any())
            ->method('isFeaturesEnabled')
            ->willReturn(true);

        $this->assertTrue($featureCheckerHolder->isFeaturesEnabled());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGetData(?EntityMetadata $metadata, ?string $expected)
    {
        $activityList = $this->createMock(ActivityList::class);
        $activityList->expects($this->once())
            ->method('getRelatedActivityId')
            ->willReturn(42);

        $user = new User();

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($user)
            ->willReturn('test name');

        $emailAddress = new EmailAddress();
        $emailAddress->setOwner($user);
        $email = new Email();
        ReflectionUtil::setId($email, 42);
        $email->setSubject('test subject');
        $email->setSentAt(new \DateTime('2018-03-23T11:43:15+00:00'));
        $email->setFromEmailAddress($emailAddress);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($email);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->willReturn($em);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with(User::class)
            ->willReturn($metadata);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $this->urlGenerator->expects($this->any())
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

    public function testGetGroupedEntitiesWithWrongEntityPassed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Argument must be instance of "%s", "%s" given',
            Email::class,
            ActivityList::class
        ));

        $entity = new ActivityList();
        $this->emailActivityListProvider->getGroupedEntities($entity, EmailAddress::class, 1);
    }

    public function getDataProvider(): array
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
