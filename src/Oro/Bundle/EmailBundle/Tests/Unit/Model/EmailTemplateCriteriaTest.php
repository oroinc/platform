<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

class EmailTemplateCriteriaTest extends \PHPUnit\Framework\TestCase
{
    private const TEMPLATE_NAME = 'template_name';
    private const TEMPLATE_ENTITY_NAME = 'template_entity_name';

    public function testEmailTemplateCriteriaWithoutEntityName(): void
    {
        $emailTemplateCriteria = new EmailTemplateCriteria(self::TEMPLATE_NAME);

        self::assertEquals(self::TEMPLATE_NAME, $emailTemplateCriteria->getName());
        self::assertNull($emailTemplateCriteria->getEntityName());
    }

    public function testEmailTemplateCriteriaWithEntityName(): void
    {
        $emailTemplateCriteria = new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME);

        self::assertEquals(self::TEMPLATE_NAME, $emailTemplateCriteria->getName());
        self::assertEquals(self::TEMPLATE_ENTITY_NAME, $emailTemplateCriteria->getEntityName());
    }
}
