<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;

class AddressStub implements AddressInterface, FullNameInterface
{
    /**
     * @var string
     */
    protected $regionCode;

    /**
     * @var string
     */
    protected $street2;

    /**
     * @param string $street2
     */
    public function __construct($street2 = null)
    {
        $this->street2 = $street2;
    }

    /**
     * @param string $code
     * @return AddressStub
     */
    public function setRegionCode($code)
    {
        $this->regionCode = $code;
        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getFirstName()
    {
        return 'Formatted';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getLastName()
    {
        return 'User';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMiddleName()
    {
        return 'Name';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getNamePrefix()
    {
        return '';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getNameSuffix()
    {
        return '';
    }

    /**
     * Get street
     *
     * @return string
     */
    #[\Override]
    public function getStreet()
    {
        return '1 Tests str.';
    }

    /**
     * Get street2
     *
     * @return string
     */
    #[\Override]
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * Get city
     *
     * @return string
     */
    #[\Override]
    public function getCity()
    {
        return 'New York';
    }

    /**
     * Get region
     *
     * @return string
     */
    #[\Override]
    public function getRegionName()
    {
        return 'New York';
    }

    /**
     * Get region code.
     *
     * @return string
     */
    #[\Override]
    public function getRegionCode()
    {
        return $this->regionCode;
    }

    /**
     * Get postal_code
     *
     * @return string
     */
    #[\Override]
    public function getPostalCode()
    {
        return '12345';
    }

    /**
     * Get country
     *
     * @return string
     */
    #[\Override]
    public function getCountryName()
    {
        return 'United States';
    }

    /**
     * Get country ISO3 code.
     *
     * @return string
     */
    #[\Override]
    public function getCountryIso3()
    {
        return 'USA';
    }

    /**
     * Get country ISO2 code.
     *
     * @return string
     */
    #[\Override]
    public function getCountryIso2()
    {
        return 'US';
    }

    /**
     * Get organization.
     *
     * @return string
     */
    #[\Override]
    public function getOrganization()
    {
        return 'Company Ltd.';
    }
}
