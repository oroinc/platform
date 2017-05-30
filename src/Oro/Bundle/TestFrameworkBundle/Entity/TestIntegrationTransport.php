<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Symfony\Component\HttpFoundation\ParameterBag;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * @ORM\Entity
 */
class TestIntegrationTransport extends Transport
{
    /** @var ParameterBag|null */
    protected $settingsBag;

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settingsBag) {
            $this->settingsBag = new ParameterBag([]);
        }

        return $this->settingsBag;
    }
}
