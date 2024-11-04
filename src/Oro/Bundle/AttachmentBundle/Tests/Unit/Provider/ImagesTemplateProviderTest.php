<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\ImagesTemplateProvider;
use PHPUnit\Framework\TestCase;

class ImagesTemplateProviderTest extends TestCase
{
    private ImagesTemplateProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ImagesTemplateProvider();
    }

    public function testGetTemplate(): void
    {
        self::assertEquals('@OroAttachment/Twig/image.html.twig', $this->provider->getTemplate());
    }

    public function testSetTemplate(): void
    {
        $template = '@ACMEAttachment/Twig/image.html.twig';

        $this->provider->setTemplate($template);

        self::assertEquals($template, $this->provider->getTemplate());
    }
}
