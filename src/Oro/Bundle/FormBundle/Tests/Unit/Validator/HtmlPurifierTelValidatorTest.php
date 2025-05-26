<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator;

use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;
use PHPUnit\Framework\TestCase;

class HtmlPurifierTelValidatorTest extends TestCase
{
    private \HTMLPurifier_URI $uri;
    private HtmlPurifierTelValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->uri = new \HTMLPurifier_URI('scheme', 'userinfo', 'host', 'port', 'path', 'query', 'fragment');

        $this->validator = new HtmlPurifierTelValidator();
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testPhones(string $phone, bool $expectedValue): void
    {
        $this->uri->path = $phone;
        $this->assertEquals($expectedValue, $this->validator->doValidate($this->uri, null, null));
    }

    public function phoneProvider(): array
    {
        return [
            [
                'phone' => '123456789',
                'expectedValue' => true
            ],
            [
                'phone' => '123-456-789',
                'expectedValue' => true
            ],
            [
                'phone' => '(123)-456-789',
                'expectedValue' => true
            ],
            [
                'phone' => '123.456.789',
                'expectedValue' => true
            ],
            [
                'phone' => 'not phone',
                'expectedValue' => false
            ],
            [
                'phone' => '654 75456',
                'expectedValue' => false
            ]
        ];
    }
}
