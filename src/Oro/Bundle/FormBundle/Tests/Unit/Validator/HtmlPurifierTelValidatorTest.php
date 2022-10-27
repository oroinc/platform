<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator;

use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;

class HtmlPurifierTelValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \HTMLPurifier_URI */
    private $uri;

    /** @var HtmlPurifierTelValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->uri = new \HTMLPurifier_URI('scheme', 'userinfo', 'host', 'port', 'path', 'query', 'fragment');

        $this->validator = new HtmlPurifierTelValidator();
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testPhones(string $phone, bool $expectedValue)
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
