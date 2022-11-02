<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\Validator\Constraints as Assert;

class TestObject
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string
     * @Assert\Length(min=5, allowEmptyString="false", groups={"api"})
     * @Assert\Length(min=3, allowEmptyString="false", groups={"ui"})
     */
    private $description;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return bool|null
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool|null $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }
}
