<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changes FieldConfigModel type's with html_escaped type
 */
class UpdateHtmlEscapedTypeMigration implements Migration
{
    /** @var EntityManager */
    private $entityManager;

    /** @var FieldConfigModel[] */
    private $htmlEscapedFields;

    /**
     * @param EntityManager $entityManager
     * @param FieldConfigModel[] $htmlEscapedFields
     */
    public function __construct(
        EntityManager $entityManager,
        array $htmlEscapedFields
    ) {
        $this->entityManager = $entityManager;
        $this->htmlEscapedFields = $htmlEscapedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        foreach ($this->htmlEscapedFields as $htmlEscapedField) {
            $htmlEscapedField->setType('text');
            $this->entityManager->persist($htmlEscapedField);
        }
        $this->entityManager->flush();
    }
}
