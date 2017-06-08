<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_nested_objects")
 * @ORM\Entity
 */
class TestEntityForNestedObjects implements TestFrameworkEntityInterface
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
     * @var TestNestedName
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", nullable=true)
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", nullable=true)
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="related_class", type="string", nullable=true)
     */
    protected $relatedClass;

    /**
     * @var string
     *
     * @ORM\Column(name="related_id", type="integer", nullable=true)
     */
    protected $relatedId;

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
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return TestNestedName
     */
    public function getName()
    {
        if (!$this->name) {
            $this->name = new TestNestedName($this->firstName, $this->lastName);
        }
        return $this->name;
    }

    /**
     * @param TestNestedName $name
     *
     * @return self
     */
    public function setName(TestNestedName $name)
    {
        $this->name = $name;
        $this->firstName = $this->name->getFirstName();
        $this->lastName = $this->name->getLastName();

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
     * @param string $firstName
     *
     * @return self
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return self
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedClass()
    {
        return $this->relatedClass;
    }

    /**
     * @param string $relatedClass
     *
     * @return self
     */
    public function setRelatedClass($relatedClass)
    {
        $this->relatedClass = $relatedClass;

        return $this;
    }

    /**
     * @return int
     */
    public function getRelatedId()
    {
        return $this->relatedId;
    }

    /**
     * @param int $relatedId
     *
     * @return self
     */
    public function setRelatedId($relatedId)
    {
        $this->relatedId = $relatedId;

        return $this;
    }
}
