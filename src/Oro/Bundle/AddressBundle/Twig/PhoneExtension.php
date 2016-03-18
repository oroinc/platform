<?php

namespace Oro\Bundle\AddressBundle\Twig;

use Oro\Bundle\AddressBundle\Provider\PhoneProvider;

class PhoneExtension extends \Twig_Extension
{
    /** @var PhoneProvider */
    protected $provider;

    /**
     * @param PhoneProvider $provider
     */
    public function __construct(PhoneProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'phone_number'  => new \Twig_Function_Method($this, 'getPhoneNumber'),
            'phone_numbers' => new \Twig_Function_Method($this, 'getPhoneNumbers'),
        ];
    }

    /**
     * @param object $obj
     *
     * @return string
     */
    public function getPhoneNumber($obj)
    {
        if (!$obj) {
            return null;
        }

        return $this->provider->getPhoneNumber($obj);
    }

    /**
     * @param object $obj
     *
     * @return string
     */
    public function getPhoneNumbers($obj)
    {
        if (!$obj) {
            return null;
        }

        return $this->provider->getPhoneNumbers($obj);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'oro_phone_extension';
    }
}
