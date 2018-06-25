<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Guesser;

use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;

class MimeTypeExtensionGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var MimeTypeExtensionGuesser */
    protected $guesser;

    public function setUp()
    {
        $this->guesser = new MimeTypeExtensionGuesser();
    }

    /**
     * @dataProvider guessDataProvider
     */
    public function testGuess($mimeType, $expectedExtension)
    {
        $this->assertEquals($expectedExtension, $this->guesser->guess($mimeType));
    }

    public function guessDataProvider()
    {
        return [
            [
                'application/vnd.ms-outlook',
                'msg',
            ],
            [
                'nonExisting',
                null,
            ],
        ];
    }
}
