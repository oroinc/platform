<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Laminas\Mail\Header\ContentType;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailBody;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class EmailBodyTest extends TestCase
{
    use EntityTestCaseTrait;

    private EmailBody $emailBody;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailBody = new EmailBody();
    }

    /**
     * Test setters getters
     */
    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(
            $this->emailBody,
            [
                ['content', 'testContent'],
                ['bodyIsText', true],
            ]
        );
    }

    /**
     * @dataProvider originalContentTypeProvider
     */
    public function testOriginalContentType(string|ContentType $contentType, string $expected): void
    {
        $this->emailBody->setOriginalContentType($contentType);

        $this->assertEquals($expected, $this->emailBody->getOriginalContentType());
    }

    public function originalContentTypeProvider(): array
    {
        return [
            'string' => [
                'contentType' => 'text/html',
                'expected' => 'text/html',
            ],
            'object' => [
                'contentType' => ContentType::fromString('Content-Type: text/plain'),
                'expected' => 'text/plain',
            ]
        ];
    }
}
