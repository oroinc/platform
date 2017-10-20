<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Faker\Provider;

use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use Oro\Bundle\TestFrameworkBundle\Provider\XssPayloadProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class XssProvider extends BaseProvider
{
    /**
     * @var int
     */
    protected static $idx = 0;

    /**
     * @var XssPayloadProvider
     */
    private $payloadProvider;
    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @param Generator $generator
     * @param XssPayloadProvider $payloadProvider
     */
    public function __construct(
        Generator $generator,
        XssPayloadProvider $payloadProvider
    ) {
        parent::__construct($generator);
        $this->payloadProvider = $payloadProvider;
        $this->passwordEncoder = new MessageDigestPasswordEncoder();
    }

    /**
     * @param string $identifier
     * @param string $payloadType
     * @return string
     */
    public function xss($identifier = 'XSS', $payloadType = 'script')
    {
        $elementId = 'p' . ++self::$idx;
        $jsPayload = sprintf('_x(\'%s\',\'%s\');', $elementId, $identifier);

        return sprintf($this->payloadProvider->getPayload($payloadType, $jsPayload, $elementId));
    }

    /**
     * @param string $password
     * @param string $salt
     * @return string
     */
    public function userPassword($password, $salt)
    {
        return $this->passwordEncoder->encodePassword($password, $salt);
    }
}
