<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\LoggedUserVariablesProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoggedUserVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var LoggedUserVariablesProvider */
    private $provider;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->provider = new LoggedUserVariablesProvider(
            $translator,
            $this->tokenAccessor,
            $this->entityNameResolver,
            $this->configManager,
            $this->htmlTagHelper
        );
    }

    public function testGetVariableDefinitionsWithoutLoggedUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [],
            $result
        );
    }

    public function testGetVariableDefinitionsForNonOroUser()
    {
        $user = $this->createMock(UserInterface::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'userName' => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.user_name'
                ],
                'userSignature' => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.siganture',
                    'filter' => 'oro_html_strip_tags'
                ],
            ],
            $result
        );
    }

    public function testGetVariableDefinitions()
    {
        $organization = new Organization();
        $user = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'userName'         => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_name'],
                'userFirstName'    => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_first_name'],
                'userLastName'     => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_last_name'],
                'userFullName'     => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_full_name'],
                'organizationName' => ['type' => 'string', 'label' => 'oro.email.emailtemplate.organization_name'],
                'userSignature'    => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.siganture',
                    'filter' => 'oro_html_strip_tags'
                ],
            ],
            $result
        );
    }

    public function testGetVariableValuesWithoutLoggedUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => '',
                'userFirstName'    => '',
                'userLastName'     => '',
                'userFullName'     => '',
                'organizationName' => '',
                'userSignature'    => '',
            ],
            $result
        );
    }

    public function testGetVariableValuesForNonOroUser()
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUsername')
            ->willReturn('test');

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => 'test',
                'userFirstName'    => '',
                'userLastName'     => '',
                'userFullName'     => '',
                'organizationName' => '',
                'userSignature'    => '',
            ],
            $result
        );
    }

    public function testGetVariableValues()
    {
        $organization = new Organization();
        $organization->setName('TestOrg');

        $user = new User();
        $user->setUsername('test');
        $user->setFirstName('FirstName');
        $user->setLastName('LastName');

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($user))
            ->willReturn('FullName');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_email.signature')
            ->willReturn('Signature');
        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with('Signature')
            ->willReturn('Sanitized Signature');

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => 'test',
                'userFirstName'    => 'FirstName',
                'userLastName'     => 'LastName',
                'userFullName'     => 'FullName',
                'organizationName' => 'TestOrg',
                'userSignature'    => 'Sanitized Signature',
            ],
            $result
        );
    }
}
