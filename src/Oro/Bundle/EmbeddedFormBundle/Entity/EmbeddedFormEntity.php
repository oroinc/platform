<?php

namespace Oro\Bundle\EmbeddedFormBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @var string
     * @ORM\Column(name="title", type="text")
     *
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id")
     *
     */
    protected $channel;

    /**
     * @var string
     * @ORM\Column(name="css", type="text")
     *
     * @Assert\NotBlank()
     */
    protected $css;

    /**
     * @var string
     *
     * @ORM\Column(name="form_type", type="string", length=255)
     *
     * @Assert\NotBlank()
     */
    protected $formType;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \Oro\Bundle\IntegrationBundle\Entity\Channel $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return \Oro\Bundle\IntegrationBundle\Entity\Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $css
     */
    public function setCss($css)
    {
        $this->css = $css;
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

}
