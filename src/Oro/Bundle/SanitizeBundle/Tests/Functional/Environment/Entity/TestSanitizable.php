<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity()]
#[ORM\Table(name: 'test_sanitizable_entity')]
#[Config(defaultValues: ['sanitize' => ['raw_sqls' => ["-- Idle comment for 'TestSanitizable' entity"]]])]
class TestSanitizable implements
    TestFrameworkEntityInterface,
    MiddleNameInterface,
    LastNameInterface,
    EmailHolderInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id()]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'first_name', type: "string", length: 255, nullable: true)]
    protected ?string $firstName = null;

    #[ORM\Column(name: 'middle_name', type: "string", length: 255, nullable: true)]
    protected ?string $middleName = null;

    #[ORM\Column(name: 'last_name', type: "string", length: 255, nullable: true)]
    protected ?string $lastName;

    #[ORM\Column(name: 'birthday', type: "datetime", nullable: true)]
    protected ?\DateTime $birthday = null;

    #[ORM\Column(name: 'email', type: "string", length: 255, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(name: 'emailunguessable', type: "string", length: 255, nullable: true)]
    protected ?string $emailunguessable = null;

    #[ORM\Column(name: 'phone', type: "string", length: 255, nullable: true)]
    protected ?string $phone = null;

    #[ORM\Column(name: 'phone_second', type: "string", length: 255, nullable: true)]
    protected ?string $phoneSecond = null;

    #[ORM\Column(name: 'secret', type: "crypted_string", length: 255, nullable: false)]
    protected ?string $secret = null;

    #[ORM\Column(name: 'text_secret', type: "crypted_text", nullable: false)]
    protected ?string $textSecret = null;

    #[ORM\Column(name: 'state_data', type: "array")]
    protected ?array $stateData = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $middleName
     *
     * @return $this
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param \DateTime|null $birthday
     *
     * @return $this
     */
    public function setBirthday(\DateTime $birthday = null)
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $emailunguessable
     *
     * @return $this
     */
    public function setEmailunguessable($emailunguessable)
    {
        $this->emailunguessable = $emailunguessable;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailunguessable()
    {
        return $this->emailunguessable;
    }

    /**
     * @param string $phone
     *
     * @return $this
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phoneSecond
     *
     * @return $this
     */
    public function setPhoneSecond(string $phoneSecond)
    {
        $this->phoneSecond = $phoneSecond;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneSecond()
    {
        return $this->phoneSecond;
    }

    /**
     * @param string $secret
     *
     * @return $this
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    public function setTextSecret(string $textSecret): self
    {
        $this->textSecret = $textSecret;

        return $this;
    }

    public function getTextSecret(): ?string
    {
        return $this->textSecret;
    }

    /**
     * @return array
     */
    public function getStateData()
    {
        return $this->stateData;
    }

    /**
     * @param array $stateData
     * @return $this
     */
    public function setStateData(array $stateData)
    {
        $this->stateData = $stateData;

        return $this;
    }
}
