<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\EmailBundle\Entity\EmailInterface;

class EmailAddressHelper
{
    protected $nameSeparators = [
        '.', '_', '-'
    ];

    /**
     * Extract 'pure' email address from the given email address
     *
     * Examples:
     *    email address: "John Smith" <john@example.com>; 'pure' email address john@example.com
     *    email address: John Smith <john@example.com>; 'pure' email address john@example.com
     *    email address: <john@example.com>; 'pure' email address john@example.com
     *    email address: john@example.com; 'pure' email address john@example.com
     *
     * @param string $fullEmailAddress
     * @return string
     */
    public function extractPureEmailAddress($fullEmailAddress)
    {
        $atPos = strrpos($fullEmailAddress, '@');
        if ($atPos === false) {
            return $fullEmailAddress;
        }

        $startPos = strrpos($fullEmailAddress, '<', -(strlen($fullEmailAddress) - $atPos));
        if ($startPos === false) {
            return $fullEmailAddress;
        }

        $endPos = strpos($fullEmailAddress, '>', $atPos);
        if ($endPos === false) {
            return $fullEmailAddress;
        }

        return substr($fullEmailAddress, $startPos + 1, $endPos - $startPos - 1);
    }

    /**
     * Extract email address name from the given email address
     *
     * Examples:
     *    email address: "John Smith" <john@example.com>; email address name John Smith
     *    email address: John Smith <john@example.com>; email address name John Smith
     *    email address: <john@example.com>; email address name is null
     *    email address: john@example.com; email address name is null
     *
     * @param string $fullEmailAddress
     * @return string|null
     */
    public function extractEmailAddressName($fullEmailAddress)
    {
        $addrPos = strrpos($fullEmailAddress, '<');
        if ($addrPos === false) {
            return null;
        }

        $result = trim(substr($fullEmailAddress, 0, $addrPos), ' "\'');

        return empty($result) ? null : $result;
    }

    /**
     * Extract first name from given email address.
     *
     * Examples:
     *    email address: John Smith IV. <john@example.com>; first name: John
     *    email address: John Smith <john@example.com>;     first name: John
     *    email address: John <john@example.com>;           first name: John
     *    email address: john.smith@example.com;            first name: john
     *    email address: john@example.com;                  first name: john
     *
     * @param string $address
     *
     * @return string|null
     */
    public function extractEmailAddressFirstName($address)
    {
        $fullName = $this->extractEmailAddressName($address);

        if ($fullName !== null) {
            $names = explode(' ', $fullName);

            return $names[0];
        }

        return $this->extractNamesFromAddress($address)[0];
    }

    /**
     * Extract last name from given email address.
     *
     * Examples:
     *    email address: John Smith IV. <john@example.com>; last name: Smith IV.
     *    email address: John Smith <john@example.com>;     last name: Smith
     *    email address: John <john@example.com>;           last name: example.com
     *    email address: john.smith@example.com;            last name: smith
     *    email address: john@example.com;                  last name: example.com
     *
     * @param string $address
     *
     * @return string|null
     */
    public function extractEmailAddressLastName($address)
    {
        $fullName = $this->extractEmailAddressName($address);

        if ($fullName !== null) {
            $names = explode(' ', $fullName);
            unset($names[0]);

            if (!empty($names)) {
                return implode(' ', $names);
            }
        }

        return $this->extractNamesFromAddress($address)[1];
    }

    /**
     * Extracts first and last name from pure email address. This method uses address itself to determine name.
     * It will parse name of the address (part before @ sign) and try to split it using common separation symbols
     * for names. If that is not possible, it will use name of address as first name and domain as last name.
     * This method always returns something so it can be used as fallback for cases when there are names always
     * required.
     *
     * @param string $address Full or pure email address.
     *
     * @return array['FirstName', 'LastName']
     */
    protected function extractNamesFromAddress($address)
    {
        $address = $this->extractPureEmailAddress($address);

        $atPos = strrpos($address, '@');

        if ($atPos === false) {
            return [$address, $address];
        }

        $domain  = substr($address, $atPos + 1);
        $address = substr($address, 0, $atPos);

        foreach ($this->nameSeparators as $separator) {
            $sepPos = strpos($address, $separator);
            if ($sepPos === false) {
                continue;
            }

            $firstName = substr($address, 0, $sepPos);
            $lastName  = substr($address, $sepPos + 1);

            if (!empty($firstName) && !empty($lastName)) {
                return [$firstName, $lastName];
            }
        }

        return [$address, $domain];
    }

    /**
     * Extract email addresses from the given argument.
     * Always return an array, even if no any email is given.
     *
     * @param $emails
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function extractEmailAddresses($emails)
    {
        if (is_string($emails)) {
            return empty($emails)
                ? array()
                : array($emails);
        }
        if (!is_array($emails) && !($emails instanceof \Traversable)) {
            throw new \InvalidArgumentException('The emails argument must be a string, array or collection.');
        }

        $result = array();
        foreach ($emails as $email) {
            if (is_string($email)) {
                $result[] = $email;
            } elseif ($email instanceof EmailInterface) {
                $result[] = $email->getEmail();
            } else {
                throw new \InvalidArgumentException(
                    'Each item of the emails collection must be a string or an object of EmailInterface.'
                );
            }
        }

        return $result;
    }

    /**
     * Build a full email address from the given 'pure' email address and email address owner name
     *
     * Examples of full email addresses:
     *    John Smith <john@example.com>, if 'pure' email address is john@example.com and owner name is 'John Smith'
     *    John <john@example.com>, if 'pure' email address is john@example.com and owner name is 'John'
     *    john@example.com, if 'pure' email address is john@example.com and owner name is empty
     *
     * @param string $pureEmailAddress
     * @param string $emailAddressOwnerName
     * @return string
     */
    public function buildFullEmailAddress($pureEmailAddress, $emailAddressOwnerName)
    {
        if ($pureEmailAddress === null) {
            $pureEmailAddress = '';
        }

        if (empty($emailAddressOwnerName)) {
            return trim($pureEmailAddress);
        }

        return sprintf('"%s" <%s>', trim($emailAddressOwnerName), trim($pureEmailAddress));
    }

    /**
     * Determine whether the given string represents a full email address or not.
     * The full email address is an address contains both an name and email parts.
     *
     * @param string $emailAddress
     * @return bool
     */
    public function isFullEmailAddress($emailAddress)
    {
        if (empty($emailAddress)) {
            return false;
        }

        return (strpos($emailAddress, '<') !== false);
    }
}
