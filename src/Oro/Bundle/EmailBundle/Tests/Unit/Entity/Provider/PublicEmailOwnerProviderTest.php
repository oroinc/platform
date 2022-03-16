<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\PublicEmailOwnerProvider;

class PublicEmailOwnerProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PublicEmailOwnerProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new PublicEmailOwnerProvider([\stdClass::class]);
    }

    public function testIsPublicEmailOwner()
    {
        $publicEmailOwnerClass = \stdClass::class;
        $extendedPublicEmailOwnerClass = get_class($this->createMock($publicEmailOwnerClass));
        $privateEmailOwnerClass = Email::class;

        self::assertTrue($this->provider->isPublicEmailOwner($publicEmailOwnerClass));
        self::assertTrue($this->provider->isPublicEmailOwner($extendedPublicEmailOwnerClass));
        self::assertFalse($this->provider->isPublicEmailOwner($privateEmailOwnerClass));

        // test memory cache
        self::assertTrue($this->provider->isPublicEmailOwner($publicEmailOwnerClass));
        self::assertTrue($this->provider->isPublicEmailOwner($extendedPublicEmailOwnerClass));
        self::assertFalse($this->provider->isPublicEmailOwner($privateEmailOwnerClass));

        // test after reset memory cache
        $this->provider->reset();
        self::assertTrue($this->provider->isPublicEmailOwner($publicEmailOwnerClass));
        self::assertTrue($this->provider->isPublicEmailOwner($extendedPublicEmailOwnerClass));
        self::assertFalse($this->provider->isPublicEmailOwner($privateEmailOwnerClass));
    }
}
