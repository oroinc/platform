<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Yaml\Yaml;

/**
 * This class provides XSS attack vectors for different cases
 */
class XssPayloadProvider
{
    const DEFAULT_JS_PAYLOAD = 'alert(1)';
    const DEFAULT_PAYLOAD_TYPE = 'script';

    /**
     * @var array
     */
    private $payloads = [];

    /**
     * @param string $jsPayload
     * @param string|null $type
     * @param null|string $elementId
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getPayload(
        $jsPayload = self::DEFAULT_JS_PAYLOAD,
        $type = null,
        $elementId = null
    ) {
        if (!$elementId) {
            $elementId = UUIDGenerator::v4();
        }

        $payload = $this->getPayloadString($type);

        return sprintf($payload, $elementId, $jsPayload);
    }

    /**
     * @return array
     */
    protected function getPayloads(): array
    {
        if (!$this->payloads) {
            $value = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/xss/xss_payloads.yml'));
            $this->payloads = $value['xss_payloads'] ?? [];
        }

        return $this->payloads;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getPayloadString($type)
    {
        $payload = getenv('XSS_PAYLOAD');
        if ($payload) {
            return $payload;
        }

        $payloadType = $this->getPayloadType($type);
        $payloads = $this->getPayloads();
        if (!array_key_exists($payloadType, $payloads)) {
            throw new \InvalidArgumentException(sprintf('Payload type "%s" unknown', $payloadType));
        }

        return $payloads[$payloadType];
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getPayloadType($type)
    {
        $payloadType = getenv('XSS_PAYLOAD_TYPE');
        if (!$payloadType) {
            $payloadType = $type;
        }
        if (!$payloadType) {
            $payloadType = self::DEFAULT_PAYLOAD_TYPE;
        }

        return $payloadType;
    }
}
