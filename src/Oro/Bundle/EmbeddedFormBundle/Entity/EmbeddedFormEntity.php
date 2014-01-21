<?php

namespace Oro\Bundle\EmbeddedFormBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_embedded_form")
 */
class EmbeddedFormEntity
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
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id")
     */
    protected $channel;

    /**
     * @var string
     * @ORM\Column(name="css", type="text")
     */
    protected $css;

    /**
     * @var string
     *
     * @ORM\Column(name="form_type", type="string", length=255)
     */
    protected $formType;
}
