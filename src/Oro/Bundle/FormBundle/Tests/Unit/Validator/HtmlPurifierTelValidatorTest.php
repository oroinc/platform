<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator;

use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;

class HtmlPurifierTelValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var HtmlPurifierTelValidator */
    protected $validator;

    /** @var \HTMLPurifier_URI */
    protected $uri;

    protected function setUp()
    {
        $this->validator = new HtmlPurifierTelValidator();
        $this->uri = new \HTMLPurifier_URI('scheme', 'userinfo', 'host', 'port', 'path', 'query', 'fragment');
    }

    /**
     * @param string $phone
     * @param bool $returnValue
     *
     * @dataProvider phoneProvider
     */
    public function testPhones($phone, $returnValue)
    {
        $this->uri->path = $phone;
        $this->assertEquals($returnValue, $this->validator->doValidate($this->uri, null, null));
    }

    /**
     * @return array
     */
    public function phoneProvider()
    {
        return [
            ['123456789', true],
            ['123-456-789', true],
            ['(123)-456-789', true],
            ['123.456.789', true],
            ['not phone', false],
            ['654 75456', false],
        ];
    }
}
