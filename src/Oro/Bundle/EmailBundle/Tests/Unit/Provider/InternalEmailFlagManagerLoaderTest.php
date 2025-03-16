<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;
use Oro\Bundle\EmailBundle\Provider\InternalEmailFlagManagerLoader;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use PHPUnit\Framework\TestCase;

class InternalEmailFlagManagerLoaderTest extends TestCase
{
    private InternalEmailFlagManagerLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->loader = new InternalEmailFlagManagerLoader();
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(bool $expectedIsSupports, EmailOrigin $origin): void
    {
        self::assertEquals($expectedIsSupports, $this->loader->supports($origin));
    }

    public function getSupportsDataProvider(): array
    {
        return [
            'supports' => [
                'expectedIsSupports' => true,
                'origin' => new InternalEmailOrigin()
            ],
            'not supports' => [
                'expectedIsSupports' => false,
                'origin' => new TestEmailOrigin()
            ]
        ];
    }

    public function testSelect(): void
    {
        $emailFolder = $this->createMock(EmailFolder::class);
        $em = $this->createMock(EntityManagerInterface::class);

        self::assertInstanceOf(InternalEmailFlagManager::class, $this->loader->select($emailFolder, $em));
    }
}
