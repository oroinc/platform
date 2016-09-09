<?php

namespace Oro\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository")
 * @ORM\Table(name="oro_translation", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="UNIQ_TRANS_LANG_KEY", columns={"language_id", "key_id"})
 * })
 */
class Translation
{
    const DEFAULT_LOCALE = 'en';

    const SCOPE_SYSTEM = 1;
    const SCOPE_UI     = 2;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var TranslationKey
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TranslationBundle\Entity\TranslationKey")
     * @ORM\JoinColumn(name="key_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $key;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @var Language
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TranslationBundle\Entity\Language")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $language;

    /**
     * @var string
     *
     * @ORM\Column(name="scope", type="smallint")
     */
    protected $scope = self::SCOPE_SYSTEM;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param TranslationKey $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return TranslationKey
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
     * @param Language $language
     * @return $this
     */
    public function setLanguage(Language $language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param integer $scope
     *
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
}
