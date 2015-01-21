<?php
namespace Oro\Bundle\UserBundle\Provider;
// use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface; Not sure what to do here
use Oro\Bundle\UserBundle\Entity\Phone;
class UserPhoneProvider //implements PhoneProviderInterface
{
    /**
     * Gets a phone number of the given User object
     *
     * @param User $object
     *
     * @return string|null
     */
    public function getPhoneNumber($object)
    {
        $primaryPhone = $object->getPrimaryPhone();
        return $primaryPhone ? $primaryPhone->getPhone() : null;
    }
    /**
     * Gets a list of all phone numbers available for the given Contact object
     *
     * @param User $object
     *
     * @return array of [phone number, phone owner]
     */
    public function getPhoneNumbers($object)
    {
        $result = [];
        foreach ($object->getPhones() as $phone) {
            $result[] = [$phone->getPhone(), $object];
        }
        return $result;
    }
}
