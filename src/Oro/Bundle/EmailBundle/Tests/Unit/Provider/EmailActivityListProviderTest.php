<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailActivityListProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailActivityListProvider
     */
    protected $emailActivityListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacadeLink;

    protected function setUp()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->setMethods(['getService', 'getRepository', 'findBy'])
            ->disableOriginalConstructor()
            ->getMock();
        $entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagHelper = $this->getMockBuilder('Oro\Bundle\UIBundle\Tools\HtmlTagHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['get', 'getToken', 'getOrganizationContext'])
            ->disableOriginalConstructor()
            ->getMock();
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($container);
        $container
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($container);
        $container
            ->expects($this->once())
            ->method('getOrganizationContext')
            ->willReturn($container);

        $this->emailActivityListProvider = new EmailActivityListProvider(
            $doctrineHelper,
            $this->securityFacadeLink,
            $entityNameResolver,
            $router,
            $configManager,
            $emailThreadProvider,
            $htmlTagHelper,
            $container
        );
    }

    public function testGetActivityOwners()
    {
        $organization = new Organization();
        $organization->setName('Org');
        $user = new User();
        $user->setUsername('test');
        $emailUser = new EmailUser();
        $emailUser->setOrganization($organization);
        $emailUser->setOwner($user);
        $owners = [$emailUser];

        $emailMock = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $activityListMock = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\ActivityList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($this->securityFacadeLink);
        $this->securityFacadeLink
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->securityFacadeLink);
        $this->securityFacadeLink
            ->expects($this->once())
            ->method('findBy')
            ->willReturn($owners);

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($emailMock, $activityListMock);

        $this->assertCount(1, $activityOwnerArray);
        $owner = $activityOwnerArray[0];
        $this->assertEquals('Org', $owner->getOrganization()->getName());
        $this->assertEquals('test', $owner->getUser()->getUsername());
    }
}
