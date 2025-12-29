<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Twig\EmailSecurityPolicyDecorator;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityPolicyInterface;

class EmailSecurityPolicyDecoratorTest extends TestCase
{
    private SecurityPolicyInterface|MockObject $baseSecurityPolicy;
    private TemplateRendererConfigProviderInterface|MockObject $templateRendererConfigProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseSecurityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $this->templateRendererConfigProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->securityPolicy = new EmailSecurityPolicyDecorator(
            $this->baseSecurityPolicy,
            $this->templateRendererConfigProvider
        );
    }

    /**
     * @dataProvider getMethodAndObjectDataProvider
     */
    public function testCheckMethodAllowedWithoutBasePolicyCheck($object, $method): void
    {
        $this->baseSecurityPolicy->expects($this->never())
            ->method('checkMethodAllowed');

        $this->templateRendererConfigProvider->expects($this->never())
            ->method('getConfiguration');

        $this->securityPolicy->checkMethodAllowed($object, $method);
    }

    public function getMethodAndObjectDataProvider(): array
    {
        return [
            [new Email(), 'getSentAs'],
            [new Email(), 'hasSome'],
            [new Email(), 'isSome'],
            [new Email(), '__toString'],
        ];
    }
}
