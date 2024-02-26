<?php

namespace Oro\Bundle\TranslationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;

/**
 * "utf8_bin" required for mysql to support case-sensitive values for "key" column
 */
#[ORM\Entity(repositoryClass: TranslationKeyRepository::class)]
#[ORM\Table(name: 'oro_translation_key', options: ['collate' => 'utf8_bin', 'charset' => 'utf8'])]
#[ORM\UniqueConstraint(name: 'oro_translation_key_uidx', columns: ['domain', 'key'])]
class TranslationKey
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: '`key`', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $key = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['default' => 'messages'])]
    protected ?string $domain = null;

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
