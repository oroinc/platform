<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

/**
 * This model is used by create and update API resources to be able to validate submitted email body.
 */
class EmailBody
{
    private ?string $type = null;
    private ?string $content = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }
}
