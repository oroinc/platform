<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Utils;

use Oro\Bundle\IntegrationBundle\Utils\SecureErrorMessageHelper;

class SecureErrorMessageHelperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider messageProvider
     *
     * @param string $exceptionMessage
     * @param string $expectedMessage
     */
    public function testSanitizeSecureInfo($exceptionMessage, $expectedMessage)
    {
        $sanitisedMessage = SecureErrorMessageHelper::sanitizeSecureInfo($exceptionMessage);
        $this->assertEquals($expectedMessage, $sanitisedMessage);
    }

    /**
     * @return array
     */
    public function messageProvider()
    {
        return [
            'some other text' => [
                '$exceptionMessage' => 'some message text',
                '$expectedMessage'  => 'some message text'
            ],
            'sanitized exception message'       => [
                '$exceptionMessage' => '<?xml version="1.0" encoding="UTF-8"?>' .
                    '<SOAP-ENV:Body><ns1:login><username xsi:type="xsd:string">abc</username>' .
                    '<apiKey xsi:type="xsd:string">abcabc1</apiKey></ns1:login></SOAP-ENV:Body></SOAP-ENV:Envelope>',
                '$expectedMessage'  => '<?xml version="1.0" encoding="UTF-8"?>' .
                    '<SOAP-ENV:Body><ns1:login><username xsi:type="xsd:string">abc</username>' .
                    '<apiKey xsi:type="xsd:string">***</apiKey></ns1:login></SOAP-ENV:Body></SOAP-ENV:Envelope>'
            ]
        ];
    }
}
