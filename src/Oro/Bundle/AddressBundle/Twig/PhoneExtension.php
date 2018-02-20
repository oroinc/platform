<?php

namespace Oro\Bundle\AddressBundle\Twig;

use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PhoneExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return PhoneProviderInterface
     */
    protected function getPhoneProvider()
    {
        return $this->container->get('oro_address.provider.phone');
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

        return $this->getPhoneProvider()->getPhoneNumber($object);
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
        $phones = $this->getPhoneProvider()->getPhoneNumbers($object);
        foreach ($phones as $row) {
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
