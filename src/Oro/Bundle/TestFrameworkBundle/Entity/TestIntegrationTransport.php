<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
* Entity that represents Test Integration Transport
*
*/
#[ORM\Entity]
class TestIntegrationTransport extends Transport
{
    /** @var ParameterBag|null */
    protected $settingsBag;

    #[\Override]
    public function getSettingsBag()
    {
        if (null === $this->settingsBag) {
            $this->settingsBag = new ParameterBag([]);
        }

        return $this->settingsBag;
    }
}
