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

    protected function setUp()
    {
        $this->loader = new InternalEmailFlagManagerLoader();
    }

    /**
     * @param bool $expectedIsSupports
     * @param EmailOrigin $origin
     *
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports($expectedIsSupports, EmailOrigin $origin)
    {
        $this->assertEquals($expectedIsSupports, $this->loader->supports($origin));
    }

    /**
     * @return array
     */
    public function getSupportsDataProvider()
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
        /** @var EmailFolder|\PHPUnit\Framework\MockObject\MockObject $emailFolder */
        $emailFolder = $this->createMock(EmailFolder::class);
        /** @var OroEntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(OroEntityManager::class);

        $this->assertInstanceOf(InternalEmailFlagManager::class, $this->loader->select($emailFolder, $em));
    }
}
