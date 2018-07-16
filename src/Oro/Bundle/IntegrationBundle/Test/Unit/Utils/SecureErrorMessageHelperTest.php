<?php

namespace Oro\Bundle\IntegrationBundle\Test\Unit\Utils;

use Oro\Bundle\IntegrationBundle\Utils\SecureErrorMessageHelper;

class SecureErrorMessageHelperTest extends \PHPUnit\Framework\TestCase
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
            'sanitized exception message'           => [
                '$exceptionMessage' => '<?xml version="1.0" encoding="UTF-8"?>' .
                    '<SOAP-ENV:Body><ns1:login><username xsi:type="xsd:string">abc</username>' .
                    '<apiKey xsi:type="xsd:string">abcabc1</apiKey></ns1:login></SOAP-ENV:Body></SOAP-ENV:Envelope>',
                '$expectedMessage'  => '<?xml version="1.0" encoding="UTF-8"?>' .
                    '<SOAP-ENV:Body><ns1:login><username xsi:type="xsd:string">abc</username>' .
                    '<apiKey xsi:type="xsd:string">***</apiKey></ns1:login></SOAP-ENV:Body></SOAP-ENV:Envelope>'
            ],
            'invalid parameter exception message'   => [
                '$exceptionMessage' => '<?xml version="1.0" encoding="UTF-8"?>'.
                    '<SOAP-ENV:Body><ns1:loginParam/><param1>api-key</param1></SOAP-ENV:Body>',
                '$expectedMessage'  => '<?xml version="1.0" encoding="UTF-8"?>'.
                    '<SOAP-ENV:Body><ns1:loginParam/><param1>***</param1></SOAP-ENV:Body>'
            ],
            'invalid parameter exception message and apiKey'   => [
                '$exceptionMessage' => '<?xml version="1.0" encoding="UTF-8"?>'.
                    '<apiKey xsi:type="xsd:string">abcabc1</apiKey></ns1:login></SOAP-ENV:Body></SOAP-ENV:Envelope>'.
                    '<SOAP-ENV:Body><ns1:loginParam/><param1>api-key</param1></SOAP-ENV:Body>',
                '$expectedMessage'  => '<?xml version="1.0" encoding="UTF-8"?>'.
                    '<apiKey xsi:type="xsd:string">***</apiKey></ns1:login></SOAP-ENV:Body></SOAP-ENV:Envelope>'.
                    '<SOAP-ENV:Body><ns1:loginParam/><param1>***</param1></SOAP-ENV:Body>'
            ]
        ];
    }
}
