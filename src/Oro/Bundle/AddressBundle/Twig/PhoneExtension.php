<?php

namespace Oro\Bundle\AddressBundle\Twig;

use Oro\Bundle\AddressBundle\Provider\PhoneProvider;

class PhoneExtension extends \Twig_Extension
{
    /**
     * @var PhoneProvider
     */
    protected $provider;

    /**
     * @param PhoneProvider $provider
     */
    public function __construct(PhoneProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('phone_number', [$this, 'getPhoneNumber']),
            new \Twig_SimpleFunction('phone_numbers', [$this, 'getPhoneNumbers']),
        ];
    }

    /**
     * @param object|null $object
     * @return string|null
     */
    public function getPhoneNumber($object)
    {
        if (!$object) {
            return null;
        }

        return $this->provider->getPhoneNumber($object);
    }

    /**
     * @param object|null $object
     * @return array [['phone' => <string>, 'object' => <object>], ...]
     */
    public function getPhoneNumbers($object)
    {
        if (!$object) {
            return [];
        }

        $result = [];
        foreach ($this->provider->getPhoneNumbers($object) as $row) {
            $result[] = ['phone' => $row[0], 'object' => $row[1]];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_phone_extension';
    }
}
