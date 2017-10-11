<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

/**
 * This class provides XSS attack vectors for different cases
 * TODO: read possible vectors from file
 */
class XssPayloadProvider
{
    const PAYLOADS = [
        'script' => '<script>%s</script>'
    ];

    const DEFAULT_JS_PAYLOAD = '<script>alert(1)</script>';
    const DEFAULT_PAYLOAD_TYPE = 'script';

    /**
     * @param string $type
     * @param string $jsPayload
     * @return string
     */
    public function getPayload($type = self::DEFAULT_PAYLOAD_TYPE, $jsPayload = self::DEFAULT_JS_PAYLOAD)
    {
        $payloadType = getenv('XSS_PAYLOAD_TYPE');
        if (!$payloadType) {
            $payloadType = $type;
        }

        return sprintf(self::PAYLOADS[$payloadType], $jsPayload);
    }
}
