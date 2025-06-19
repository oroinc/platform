<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\EmailTemplateLoader;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateChainLoader;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateLoaderInterface;
use PHPUnit\Framework\TestCase;
use Twig\Loader\ChainLoader;
use Twig\Source;

class EmailTemplateChainLoaderTest extends TestCase
{
    private ChainLoader $chainLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->chainLoader = new ChainLoader();
    }

    public function testConstruct(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);
        $emailTemplateLoader2 = $this->createMock(EmailTemplateLoaderInterface::class);

        $loader = new EmailTemplateChainLoader($this->chainLoader, [$emailTemplateLoader1, $emailTemplateLoader2]);
        self::assertEquals([$emailTemplateLoader1, $emailTemplateLoader2], $loader->getLoaders());
    }

    public function testAddLoader(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);

        $loader = new EmailTemplateChainLoader($this->chainLoader, []);
        self::assertEquals([], $loader->getLoaders());

        $loader->addLoader($emailTemplateLoader1);
        self::assertEquals([$emailTemplateLoader1], $loader->getLoaders());
    }

    public function testGetSourceContext(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);

        $name = 'sample_name';
        $sourceContext = new Source('sample_code', $name);
        $emailTemplateLoader1->expects(self::once())
            ->method('exists')
            ->with($name)
            ->willReturn(true);

        $emailTemplateLoader1->expects(self::once())
            ->method('getSourceContext')
            ->with($name)
            ->willReturn($sourceContext);

        $loader = new EmailTemplateChainLoader($this->chainLoader, [$emailTemplateLoader1]);

        self::assertSame($sourceContext, $loader->getSourceContext($name));
    }

    public function testGetCacheKey(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);

        $name = 'sample_name';
        $emailTemplateLoader1->expects(self::once())
            ->method('exists')
            ->with($name)
            ->willReturn(true);

        $emailTemplateLoader1->expects(self::once())
            ->method('getCacheKey')
            ->with($name)
            ->willReturn($name);

        $loader = new EmailTemplateChainLoader($this->chainLoader, [$emailTemplateLoader1]);

        self::assertEquals($name, $loader->getCacheKey($name));
    }

    public function testIsFresh(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);

        $name = 'sample_name';
        $emailTemplateLoader1->expects(self::once())
            ->method('exists')
            ->with($name)
            ->willReturn(true);

        $time = time();
        $emailTemplateLoader1->expects(self::once())
            ->method('isFresh')
            ->with($name, $time)
            ->willReturn(true);

        $loader = new EmailTemplateChainLoader($this->chainLoader, [$emailTemplateLoader1]);

        self::assertTrue($loader->isFresh($name, $time));
    }

    public function testExists(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);

        $name = 'sample_name';
        $emailTemplateLoader1->expects(self::once())
            ->method('exists')
            ->with($name)
            ->willReturn(true);

        $loader = new EmailTemplateChainLoader($this->chainLoader, [$emailTemplateLoader1]);

        self::assertTrue($loader->exists($name));
    }

    public function testGetEmailTemplate(): void
    {
        $emailTemplateLoader1 = $this->createMock(EmailTemplateLoaderInterface::class);

        $name = 'sample_name';
        $emailTemplate = new EmailTemplateModel();
        $emailTemplateLoader1->expects(self::once())
            ->method('exists')
            ->with($name)
            ->willReturn(true);

        $emailTemplateLoader1->expects(self::once())
            ->method('getEmailTemplate')
            ->with($name)
            ->willReturn($emailTemplate);

        $loader = new EmailTemplateChainLoader($this->chainLoader, [$emailTemplateLoader1]);

        self::assertSame($emailTemplate, $loader->getEmailTemplate($name));
    }
}
