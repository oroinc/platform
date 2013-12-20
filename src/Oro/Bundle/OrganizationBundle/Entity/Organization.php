<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\NotificationBundle\Entity\NotificationEmailInterface;

/**
 * Organization
 *
 * @ORM\Table(name="oro_organization")
 * @ORM\Entity
 * @Config(
 *  defaultValues={
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class Organization implements NotificationEmailInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
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
     * @ORM\Column(name="currency", type="string", length=3)
     */
    protected $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="currency_precision", type="string", length=10)
     */
    protected $precision;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit",
     *     mappedBy="organization",
     *     cascade={"ALL"},
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $businessUnits;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Currency
     *
     * @param string $currency
     * @return Organization
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get Currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set Precision
     *
     * @param string $precision
     * @return Organization
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Get Precision
     *
     * @return string
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @param ArrayCollection $businessUnits
     *
     * @return $this
     */
    public function setBusinessUnits($businessUnits)
    {
        $this->businessUnits = $businessUnits;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getBusinessUnits()
    {
        return $this->businessUnits;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationEmails()
    {
        $emails = [];
        $this->businessUnits->forAll(
            function (BusinessUnit $bu) use ($emails) {
                $emails = array_merge($emails, $bu->getNotificationEmails());
            }
        );

        return new ArrayCollection($emails);
    }
}
