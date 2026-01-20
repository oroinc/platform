<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\InitializeAbsoluteUrlFlag;
use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiUrlResolverTest extends TestCase
{
    private RequestStack $requestStack;
    private ApiUrlResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->resolver = new ApiUrlResolver($this->requestStack);
    }

    public function testShouldUseAbsoluteUrlsReturnsFalseWhenFlagNotSet(): void
    {
        self::assertFalse($this->resolver->shouldUseAbsoluteUrls());
    }

    public function testShouldUseAbsoluteUrlsReturnsTrueWhenFlagSet(): void
    {
        $request = new Request();
        $request->attributes->set(InitializeAbsoluteUrlFlag::ABSOLUTE_URL_FLAG, true);
        $this->requestStack->push($request);

        self::assertTrue($this->resolver->shouldUseAbsoluteUrls());
    }

    public function testGetEffectiveReferenceTypeReturnsDefaultWhenFlagNotSet(): void
    {
        self::assertSame(
            UrlGeneratorInterface::ABSOLUTE_PATH,
            $this->resolver->getEffectiveReferenceType()
        );
    }

    public function testGetEffectiveReferenceTypeReturnsCustomDefaultWhenFlagNotSet(): void
    {
        self::assertSame(
            UrlGeneratorInterface::RELATIVE_PATH,
            $this->resolver->getEffectiveReferenceType(UrlGeneratorInterface::RELATIVE_PATH)
        );
    }

    public function testGetEffectiveReferenceTypeReturnsAbsoluteUrlWhenFlagSet(): void
    {
        $request = new Request();
        $request->attributes->set(InitializeAbsoluteUrlFlag::ABSOLUTE_URL_FLAG, true);
        $this->requestStack->push($request);

        self::assertSame(
            UrlGeneratorInterface::ABSOLUTE_URL,
            $this->resolver->getEffectiveReferenceType()
        );
    }

    public function testGetEffectiveReferenceTypeIgnoresCustomDefaultWhenFlagSet(): void
    {
        $request = new Request();
        $request->attributes->set(InitializeAbsoluteUrlFlag::ABSOLUTE_URL_FLAG, true);
        $this->requestStack->push($request);

        self::assertSame(
            UrlGeneratorInterface::ABSOLUTE_URL,
            $this->resolver->getEffectiveReferenceType(UrlGeneratorInterface::RELATIVE_PATH)
        );
    }
}
