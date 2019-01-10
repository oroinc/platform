<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Model\FieldsTransformer;

class FieldsTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldsTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new FieldsTransformer();
    }

    public function testGetDataShouldRetrieveOldFormadUsingFields()
    {
        $oldDate = new \DateTime();
        $newDate = new \DateTime();

        $fields = [];
        $fields[] = new AuditField('field', 'integer', 1, 0);
        $fields[] = new AuditField('field2', 'string', 'new_', '_old');
        $fields[] = new AuditField('field3', 'date', $newDate, $oldDate);
        $fields[] = new AuditField('field4', 'datetime', $newDate, $oldDate);
        $auditFieldWithTranslationDomain = new AuditField('field5', 'string', 'new_translatable', 'old_translatable');
        $auditFieldWithTranslationDomain->setTranslationDomain('message');
        $fields[] = $auditFieldWithTranslationDomain;

        $this->assertSame(
            [
                'field' => ['old' => 0, 'new' => 1],
                'field2' => ['old' => '_old', 'new' => 'new_'],
                'field3' => [
                    'old' => ['value' => $oldDate, 'type' => 'date'],
                    'new' => ['value' => $newDate, 'type' => 'date'],
                ],
                'field4' => [
                    'old' => ['value' => $oldDate, 'type' => 'datetime'],
                    'new' => ['value' => $newDate, 'type' => 'datetime'],
                ],
                'field5' => [
                    'old' => 'old_translatable',
                    'new' => 'new_translatable',
                    'translationDomain' => 'message',
                ],
            ],
            $this->transformer->getData($fields)
        );
    }
}
