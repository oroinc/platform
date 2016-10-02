<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultMenuUpdateProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var MenuUpdateProvider
     */
    protected $defaultMenuUpdateProvider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultMenuUpdateProvider = new MenuUpdateProvider($this->securityFacade, $this->doctrineHelper);
    }

    public function testGetUpdates()
    {
        $result = [new MenuUpdate(), new MenuUpdate()];

        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade->expects($this->any())->method('getOrganization')
            ->will($this->returnValue($organization));
        $this->securityFacade->expects($this->any())->method('getLoggedUser')
            ->will($this->returnValue($user));

        $menuUpdateRepository = $this->getMockBuilder(MenuUpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $menuUpdateRepository->expects($this->once())
            ->method('getMenuUpdates')
            ->with(MenuUpdateData::MENU, $organization, $user)
            ->will($this->returnValue($result));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroNavigationBundle:MenuUpdate')
            ->willReturn($menuUpdateRepository);

        $updates = $this->defaultMenuUpdateProvider->getUpdates(MenuUpdateData::MENU, MenuUpdate::OWNERSHIP_USER);

        $this->assertEquals($result, $updates);
    }
}
