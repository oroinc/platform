<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\NavigationBundle\Menu\DefaultMenuUpdateProvider;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;

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
     * @var DefaultMenuUpdateProvider
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

        $this->defaultMenuUpdateProvider = new DefaultMenuUpdateProvider($this->securityFacade, $this->doctrineHelper);
    }

    public function testGetUpdates()
    {
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $businessUnit = $this->getMockBuilder(BusinessUnit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $businessUnit->expects($this->any())->method('getOrganization')->will($this->returnValue($organization));
        $businessUnits = new ArrayCollection([$businessUnit]);

        $user->expects($this->any())->method('getBusinessUnits')->will($this->returnValue($businessUnits));

        $this->securityFacade->expects($this->any())->method('getOrganization')
            ->will($this->returnValue($organization));
        $this->securityFacade->expects($this->any())->method('getLoggedUser')
            ->will($this->returnValue($user));

        $menuUpdateRepository = $this->getMockBuilder(MenuUpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $menuUpdateRepository->expects($this->once())
            ->method('getMenuUpdates')
            ->with(MenuUpdateData::MENU, $organization, $businessUnit, $user);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroNavigationBundle:MenuUpdate')
            ->willReturn($menuUpdateRepository);

        $this->defaultMenuUpdateProvider->getUpdates(MenuUpdateData::MENU);
    }
}
