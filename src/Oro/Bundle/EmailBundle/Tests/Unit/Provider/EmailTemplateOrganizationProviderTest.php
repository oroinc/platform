<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EmailTemplateOrganizationProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTemplateOrganizationProviderTest extends TestCase
{
    private tokenAccessorInterface|MockObject $tokenAccessor;
    private EmailTemplateOrganizationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->provider = new EmailTemplateOrganizationProvider($this->tokenAccessor);
    }

    public function testLoadEmailTemplateOrganization(): void
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $actualOrganization = $this->provider->getOrganization();
        self::assertInstanceOf(OrganizationInterface::class, $actualOrganization);
        self::assertEquals($organization->getId(), $actualOrganization->getId());
    }
}
