<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_custom_article')]
class TestCustomArticle implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'headline', type: Types::STRING, length: 255)]
    private ?string $headline = null;

    #[ORM\Column(name: 'body', type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(name: 'body_length', type: Types::INTEGER)]
    private int $bodyLength = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * @param string $headline
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
        $this->bodyLength = $body ? \strlen($body) : 0;
    }

    /**
     * @return int|null
     */
    public function getBodyLength()
    {
        return $this->bodyLength;
    }

    /**
     * @param int|null $bodyLength
     */
    public function setBodyLength($bodyLength)
    {
        $this->bodyLength = $bodyLength;
    }
}
