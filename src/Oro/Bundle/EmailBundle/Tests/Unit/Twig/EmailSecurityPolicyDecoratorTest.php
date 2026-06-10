<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Twig\EmailSecurityPolicyDecorator;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityPolicyInterface;

final class EmailSecurityPolicyDecoratorTest extends TestCase
{
    private SecurityPolicyInterface&MockObject $baseSecurityPolicy;
    private TemplateRendererConfigProviderInterface&MockObject $templateRendererConfigProvider;
    private EmailSecurityPolicyDecorator $securityPolicy;

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
     * @dataProvider checkMethodAllowedSkippedProvider
     */
    public function testCheckMethodAllowedSkipsMagicToString(object $object, string $method): void
    {
        $this->baseSecurityPolicy->expects(self::never())
            ->method('checkMethodAllowed');

        $this->templateRendererConfigProvider->expects(self::never())
            ->method('getConfiguration');

        $this->securityPolicy->checkMethodAllowed($object, $method);
    }

    public static function checkMethodAllowedSkippedProvider(): iterable
    {
        yield '__toString is skipped' => [new Email(), '__toString'];
    }

    /**
     * @dataProvider checkMethodAllowedDelegatedProvider
     */
    public function testCheckMethodAllowedDelegatesToBasePolicy(object $object, string $method): void
    {
        $baseSecurityPolicy = $this->getMockBuilder(SecurityPolicyInterface::class)
            ->onlyMethods(['checkSecurity', 'checkMethodAllowed', 'checkPropertyAllowed'])
            ->addMethods(['setAllowedMethods', 'setAllowedProperties'])
            ->getMock();
        $securityPolicy = new EmailSecurityPolicyDecorator(
            $baseSecurityPolicy,
            $this->templateRendererConfigProvider
        );

        $this->templateRendererConfigProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $baseSecurityPolicy->expects(self::once())
            ->method('checkMethodAllowed')
            ->with($object, $method);

        $securityPolicy->checkMethodAllowed($object, $method);
    }

    public static function checkMethodAllowedDelegatedProvider(): iterable
    {
        yield 'getSentAs is delegated' => [new Email(), 'getSentAs'];
        yield 'hasSome is delegated' => [new Email(), 'hasSome'];
        yield 'isSome is delegated' => [new Email(), 'isSome'];
    }
}
