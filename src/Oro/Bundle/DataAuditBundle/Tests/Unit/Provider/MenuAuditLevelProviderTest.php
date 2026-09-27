<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditLevelProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuAuditLevelProviderTest extends TestCase
{
    private const string SCOPE_TYPE = 'menu_default_visibility';
    private const string PREFIX = 'Oro\Bundle\NavigationBundle\\';
    private const string SUFFIX = 'BackOfficeMenu';

    private ScopeManager&MockObject $scopeManager;
    private EntityNameResolver&MockObject $entityNameResolver;
    private MenuAuditLevelProvider $provider;

    private array $criteriaByScope = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->scopeManager->expects(self::any())
            ->method('getCriteriaByScope')
            ->willReturnCallback(fn (Scope $scope): ScopeCriteria => $this->criteriaByScope[spl_object_id($scope)]);
        $this->scopeManager->expects(self::any())
            ->method('getScopeEntities')
            ->with(self::SCOPE_TYPE)
            ->willReturn(['user' => User::class, 'organization' => Organization::class]);

        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->provider = new MenuAuditLevelProvider(
            $this->scopeManager,
            $this->entityNameResolver,
            self::SCOPE_TYPE,
            self::PREFIX,
            self::SUFFIX,
            'oro.dataaudit.back_office_menu.type.',
            'Back-Office Menu'
        );
    }

    public function testGetClassForScopeOfTheGlobalLevel(): void
    {
        self::assertSame(
            self::PREFIX . 'Global' . self::SUFFIX,
            $this->provider->getClassForScope($this->givenScopeWithCriteria([]))
        );
    }

    public function testGetClassForScopeTakesTheMostSpecificCriterion(): void
    {
        $scope = $this->givenScopeWithCriteria(['organization' => new Organization(), 'user' => new User()]);

        self::assertSame(self::PREFIX . 'User' . self::SUFFIX, $this->provider->getClassForScope($scope));
    }

    public function testGetTargetNameIsWhatTheMenuWasCustomizedFor(): void
    {
        $user = new User();
        $scope = $this->givenScopeWithCriteria(['organization' => new Organization(), 'user' => $user]);
        $this->entityNameResolver->expects(self::any())
            ->method('getName')
            ->with($user)
            ->willReturn('John Doe');

        self::assertSame('John Doe', $this->provider->getTargetName($scope));
        self::assertNull($this->provider->getTargetName($this->givenScopeWithCriteria([])));
    }

    public function testAllLevelsOfTheApplication(): void
    {
        self::assertSame(
            [
                self::PREFIX . 'Global' . self::SUFFIX => 'global',
                self::PREFIX . 'User' . self::SUFFIX => 'user',
                self::PREFIX . 'Organization' . self::SUFFIX => 'organization',
            ],
            $this->provider->all()
        );
    }

    public function testGetLabelKeyOfALevel(): void
    {
        self::assertSame(
            'oro.dataaudit.back_office_menu.type.user',
            $this->provider->getLabelKey(self::PREFIX . 'User' . self::SUFFIX)
        );
        self::assertNull($this->provider->getLabelKey(self::PREFIX . 'UnknownLevel' . self::SUFFIX));
    }

    public function testRecognizesItsOwnTypesOnly(): void
    {
        self::assertTrue($this->provider->isType(self::PREFIX . 'User' . self::SUFFIX));
        self::assertTrue($this->provider->isType(self::PREFIX . 'MyCustomLevel' . self::SUFFIX));
        self::assertFalse($this->provider->isType('Oro\Bundle\CommerceMenuBundle\CustomerStorefrontMenu'));
        self::assertFalse($this->provider->isType('Oro\Bundle\UserBundle\Entity\User'));
        self::assertFalse($this->provider->isType(null));
    }

    public function testLevelsOfAnotherKindOfMenu(): void
    {
        $scopeManager = $this->createMock(ScopeManager::class);
        $scopeManager->expects(self::any())
            ->method('getScopeEntities')
            ->with('menu_frontend_visibility')
            ->willReturn([
                'customer' => 'Oro\Bundle\CustomerBundle\Entity\Customer',
                'customerGroup' => 'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
                'website' => 'Oro\Bundle\WebsiteBundle\Entity\Website',
                'organization' => Organization::class,
            ]);

        $provider = new MenuAuditLevelProvider(
            $scopeManager,
            $this->entityNameResolver,
            'menu_frontend_visibility',
            'Oro\Bundle\CommerceMenuBundle\\',
            'StorefrontMenu',
            'oro.commercemenu.audit.type.',
            'Storefront Menu'
        );

        self::assertSame(
            [
                'Oro\Bundle\CommerceMenuBundle\GlobalStorefrontMenu' => 'global',
                'Oro\Bundle\CommerceMenuBundle\CustomerStorefrontMenu' => 'customer',
                'Oro\Bundle\CommerceMenuBundle\CustomerGroupStorefrontMenu' => 'customerGroup',
                'Oro\Bundle\CommerceMenuBundle\WebsiteStorefrontMenu' => 'website',
                'Oro\Bundle\CommerceMenuBundle\OrganizationStorefrontMenu' => 'organization',
            ],
            $provider->all()
        );
        self::assertSame(
            'oro.commercemenu.audit.type.customer_group',
            $provider->getLabelKey('Oro\Bundle\CommerceMenuBundle\CustomerGroupStorefrontMenu')
        );
        self::assertSame(
            'Storefront Menu: Customer Group',
            $provider->getGenericLabel('Oro\Bundle\CommerceMenuBundle\CustomerGroupStorefrontMenu')
        );
    }

    public function testGetGenericLabelOfALevelTheApplicationDoesNotHave(): void
    {
        self::assertSame(
            'Back-Office Menu: My Custom Level',
            $this->provider->getGenericLabel(self::PREFIX . 'MyCustomLevel' . self::SUFFIX)
        );
    }

    private function givenScopeWithCriteria(array $criteria): Scope
    {
        $scope = new Scope();
        $parameters = array_merge(['organization' => null, 'user' => null], $criteria);
        $this->criteriaByScope[spl_object_id($scope)] = new ScopeCriteria(
            $parameters,
            $this->createMock(ClassMetadataFactory::class)
        );

        return $scope;
    }
}
