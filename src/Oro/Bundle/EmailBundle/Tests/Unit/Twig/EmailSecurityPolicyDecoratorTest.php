<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Twig\EmailSecurityPolicyDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityPolicyInterface;

class EmailSecurityPolicyDecoratorTest extends TestCase
{
    private SecurityPolicyInterface|MockObject $baseSecurityPolicy;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseSecurityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $this->securityPolicy = new EmailSecurityPolicyDecorator(
            $this->baseSecurityPolicy,
        );
    }

    /**
     * @dataProvider getMethodAndObjectDataProvider
     */
    public function testCheckMethodAllowedWithoutBasePolicyCheck($object, $method): void
    {
        $this->baseSecurityPolicy->expects($this->never())
            ->method('checkMethodAllowed');

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
