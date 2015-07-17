<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Table(
 *      name="oro_email_mailbox_processor"
 * )
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=30)
 */
abstract class MailboxProcessorSettings
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns all required setting to configure processor from this entity.
     *
     * @return ParameterBag
     */
    abstract public function getSettings();

    /**
     * Returns type of processor.
     *
     * @return string
     */
    abstract public function getType();
}
