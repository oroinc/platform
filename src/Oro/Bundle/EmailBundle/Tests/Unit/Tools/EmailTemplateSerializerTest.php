<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Tools\EmailTemplateSerializer;
use PHPUnit\Framework\TestCase;

class EmailTemplateSerializerTest extends TestCase
{
    private EmailTemplateSerializer $serializer;

    #[\Override]
    protected function setUp(): void
    {
        $this->serializer = new EmailTemplateSerializer();
    }

    public function testSerializeWhenEmpty(): void
    {
        $emailTemplate = new EmailTemplate();

        self::assertEquals(
            [
                'id' => $emailTemplate->getId(),
                'name' => $emailTemplate->getName(),
                'is_system' => $emailTemplate->getIsSystem(),
                'is_editable' => $emailTemplate->getIsEditable(),
                'parent' => $emailTemplate->getParent(),
                'subject' => $emailTemplate->getSubject(),
                'content' => $emailTemplate->getContent(),
                'entity_name' => $emailTemplate->getEntityName(),
                'type' => $emailTemplate->getType(),
            ],
            $this->serializer->serialize($emailTemplate)
        );
    }

    public function testSerialize(): void
    {
        $emailTemplate = (new EmailTemplate('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content')
            ->setIsSystem(true)
            ->setIsEditable(true)
            ->setParent(42)
            ->setEntityName('stdClass')
            ->setType('html');

        self::assertEquals(
            [
                'id' => $emailTemplate->getId(),
                'name' => $emailTemplate->getName(),
                'is_system' => $emailTemplate->getIsSystem(),
                'is_editable' => $emailTemplate->getIsEditable(),
                'parent' => $emailTemplate->getParent(),
                'subject' => $emailTemplate->getSubject(),
                'content' => $emailTemplate->getContent(),
                'entity_name' => $emailTemplate->getEntityName(),
                'type' => $emailTemplate->getType(),
            ],
            $this->serializer->serialize($emailTemplate)
        );
    }
}
