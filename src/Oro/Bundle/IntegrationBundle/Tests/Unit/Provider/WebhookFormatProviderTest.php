<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use PHPUnit\Framework\TestCase;

class WebhookFormatProviderTest extends TestCase
{
    private WebhookFormatProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new WebhookFormatProvider();
    }

    public function testGetFormatsReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->provider->getFormats());
    }

    public function testAddFormat(): void
    {
        $this->provider->addFormat('json_api', 'JSON:API');

        self::assertSame(['json_api' => 'JSON:API'], $this->provider->getFormats());
    }

    public function testAddMultipleFormats(): void
    {
        $this->provider->addFormat('json_api', 'JSON:API');
        $this->provider->addFormat('flat', 'Flat JSON');

        self::assertSame(
            ['json_api' => 'JSON:API', 'flat' => 'Flat JSON'],
            $this->provider->getFormats()
        );
    }

    public function testAddFormatOverwritesExistingFormat(): void
    {
        $this->provider->addFormat('json_api', 'Old Label');
        $this->provider->addFormat('json_api', 'New Label');

        self::assertSame(['json_api' => 'New Label'], $this->provider->getFormats());
    }
}
