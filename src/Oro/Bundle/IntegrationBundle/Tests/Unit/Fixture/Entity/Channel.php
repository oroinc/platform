<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_integration_channel")
 * @ORM\Entity(repositoryClass="Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository")
 */
class Channel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;
}
