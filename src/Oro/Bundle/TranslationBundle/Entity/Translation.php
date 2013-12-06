<?php

namespace Oro\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Translation
 * @package Oro\Bundle\TranslationBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository")
 * @ORM\Table(name="oro_translation")
 */
class Translation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Type("integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Type("string")
     */
    protected $key;

    /**
     * @ORM\Column(type="text")
     * @Type("string")
     */
    protected $value;

    /**
     * @ORM\Column(type="string", length=5)
     * @Type("string")
     */
    protected $locale;

    /**
     * @ORM\Column(type="string", length=255)
     * @Type("string")
     */
    protected $domain;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
