<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Manager\DTO\EmailBody;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Zend\Mail\Header\ContentType;

class EmailBodyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var EmailBody */
    private $emailBody;

    protected function setUp()
    {
        $this->emailBody = new EmailBody();
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
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
     *
     * @param string|ContentType $contentType
     * @param string $expected
     */
    public function testOriginalContentType($contentType, $expected)
    {
        $this->emailBody->setOriginalContentType($contentType);

        $this->assertEquals($expected, $this->emailBody->getOriginalContentType());
    }

    /**
     * @return array
     */
    public function originalContentTypeProvider()
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
