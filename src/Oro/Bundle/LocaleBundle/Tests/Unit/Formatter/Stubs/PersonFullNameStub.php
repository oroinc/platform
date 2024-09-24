<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs;

use Oro\Bundle\LocaleBundle\Model\FullNameInterface;

class PersonFullNameStub implements FullNameInterface
{
    /**
     * @return string
     */
    #[\Override]
    public function getFirstName()
    {
        return 'fn';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getLastName()
    {
        return 'ln';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMiddleName()
    {
        return 'mn';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getNamePrefix()
    {
        return 'np';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getNameSuffix()
    {
        return 'ns';
    }
}
