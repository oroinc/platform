<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\FilesTemplateProvider;
use PHPUnit\Framework\TestCase;

class FilesTemplateProviderTest extends TestCase
{
    private FilesTemplateProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new FilesTemplateProvider();
    }

    public function testGetTemplate(): void
    {
        self::assertEquals('@OroAttachment/Twig/file.html.twig', $this->provider->getTemplate());
    }

    public function testSetTemplate(): void
    {
        $template = '@ACMEAttachment/Twig/file.html.twig';

        $this->provider->setTemplate($template);

        self::assertEquals($template, $this->provider->getTemplate());
    }
}
