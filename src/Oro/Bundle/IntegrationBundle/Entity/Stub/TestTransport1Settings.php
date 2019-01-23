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
class TestTransport1Settings extends Transport
{
    /**
     * @var string
     */
    private $transport1Field;

    /**
     * @return string
     */
    public function getTransport1Field()
    {
        return $this->transport1Field;
    }

    /**
     * @param string $transport1Field
     * @return $this
     */
    public function setTransport1Field(string $transport1Field)
    {
        $this->transport1Field = $transport1Field;

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
