<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Stub;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Stub entity for behat testing switching between transport types
 * It can't be put inside behat folder since entities have to be in the Entity namespace
 *
 * @ORM\Entity
 */
class TestTransport2Settings extends Transport
{
    /**
     * @var string
     */
    private $transport2Field;

    /**
     * @return string
     */
    public function getTransport2Field()
    {
        return $this->transport2Field;
    }

    /**
     * @param string $transport2Field
     * @return $this
     */
    public function setTransport2Field(string $transport2Field)
    {
        $this->transport2Field = $transport2Field;

        return $this;
    }

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        return new ParameterBag();
    }
}
