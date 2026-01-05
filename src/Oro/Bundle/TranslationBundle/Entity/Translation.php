<?php

namespace Oro\Bundle\TranslationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;

/**
* Entity that represents Translation
*
*/
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'oro_translation')]
#[ORM\UniqueConstraint(name: 'language_key_uniq', columns: ['language_id', 'translation_key_id'])]
class Translation
{
    public const SCOPE_SYSTEM = 0;
    public const SCOPE_INSTALLED = 1;
    public const SCOPE_UI = 2;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TranslationKey::class)]
    #[ORM\JoinColumn(name: 'translation_key_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?TranslationKey $translationKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $value = null;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Language $language = null;

    #[ORM\Column(name: 'scope', type: Types::SMALLINT)]
    protected ?int $scope = self::SCOPE_SYSTEM;

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
    public function setTranslationKey($key)
    {
        $this->translationKey = $key;

        return $this;
    }

    /**
     * @return TranslationKey
     */
    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
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
