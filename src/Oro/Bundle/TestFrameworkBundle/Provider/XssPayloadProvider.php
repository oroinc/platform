<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * This class provides XSS attack vectors for different cases
 * TODO: read possible vectors from file
 */
class XssPayloadProvider
{
    const PAYLOADS = [
        'script' => '<script id="%1$s">%2$s</script>'
    ];

    const DEFAULT_JS_PAYLOAD = 'alert(1)';
    const DEFAULT_PAYLOAD_TYPE = 'script';

    /**
     * @param string $type
     * @param string $jsPayload
     * @param null|string $elementId
     * @return string
     */
    public function getPayload(
        $type = self::DEFAULT_PAYLOAD_TYPE,
        $jsPayload = self::DEFAULT_JS_PAYLOAD,
        $elementId = null
    ) {
        $payloadType = getenv('XSS_PAYLOAD_TYPE');
        if (!$payloadType) {
            $payloadType = $type;
        }

        if (!$elementId) {
            $elementId = UUIDGenerator::v4();
        }

        return sprintf(self::PAYLOADS[$payloadType], $elementId, $jsPayload);
    }
}
