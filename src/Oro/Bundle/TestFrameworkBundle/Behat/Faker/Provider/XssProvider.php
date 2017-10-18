<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Faker\Provider;

use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use Oro\Bundle\TestFrameworkBundle\Provider\XssPayloadProvider;

class XssProvider extends BaseProvider
{
    protected static $idx = 0;

    /**
     * @var XssPayloadProvider
     */
    private $payloadProvider;

    /**
     * @param Generator $generator
     * @param XssPayloadProvider $payloadProvider
     */
    public function __construct(Generator $generator, XssPayloadProvider $payloadProvider)
    {
        parent::__construct($generator);
        $this->payloadProvider = $payloadProvider;
    }

    /**
     * @param string $identifier
     * @param string $payloadType
     * @return string
     */
    public function xss($identifier = 'XSS', $payloadType = 'script')
    {
        $elementId = 'p' . ++self::$idx;
        $jsPayload = sprintf('_x(\'%s\', \'%s\');', $elementId, $identifier);

        return sprintf($this->payloadProvider->getPayload($payloadType, $jsPayload, $elementId));
    }
}
