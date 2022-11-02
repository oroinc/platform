<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;
use Oro\Bundle\EmailBundle\Provider\InternalEmailFlagManagerLoader;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

class InternalEmailFlagManagerLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var InternalEmailFlagManagerLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->loader = new InternalEmailFlagManagerLoader();
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(bool $expectedIsSupports, EmailOrigin $origin)
    {
        $this->assertEquals($expectedIsSupports, $this->loader->supports($origin));
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

    public function testSelect()
    {
        $emailFolder = $this->createMock(EmailFolder::class);
        $em = $this->createMock(OroEntityManager::class);

        $this->assertInstanceOf(InternalEmailFlagManager::class, $this->loader->select($emailFolder, $em));
    }
}
