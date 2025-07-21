<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;

/**
 * Represents a PDF document as doctrine entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_pdf_generator_pdf_document')]
#[ORM\Index(
    columns: ['pdf_document_name', 'pdf_document_type', 'source_entity_class', 'source_entity_id'],
    name: 'oro_pdf_generator_pdf_document_idx'
)]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-file-pdf-o'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'dataaudit' => ['auditable' => false],
        'security' => ['type' => 'ACL', 'group_name' => ''],
    ]
)]
class PdfDocument extends AbstractPdfDocument implements
    ExtendEntityInterface,
    OrganizationAwareInterface,
    DatesAwareInterface
{
    use ExtendEntityTrait;
    use OrganizationAwareTrait;
    use DatesAwareTrait;

    /**
     * The internal ID.
     */
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * The universally unique identifier (UUID), e.g. to identify the document in URL.
     */
    #[ORM\Column(name: 'uuid', type: Types::GUID, unique: true, nullable: false)]
    protected string $uuid;

    /**
     * The name of the PDF document (e.g., order-0101).
     */
    #[ORM\Column(name: 'pdf_document_name', type: Types::STRING, length: 255, nullable: false)]
    protected string $pdfDocumentName;

    /**
     * The type of the PDF document (e.g., us_standard_invoice).
     */
    #[ORM\Column(name: 'pdf_document_type', type: Types::STRING, length: 255, nullable: false)]
    protected string $pdfDocumentType;

    /**
     * The associated file entity representing the PDF document.
     */
    #[ORM\OneToOne(targetEntity: File::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'pdf_document_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(
        defaultValues: [
            'attachment' => [
                'immutable' => true,
                'acl_protected' => true,
                'use_dam' => false,
                'file_applications' => ['default'],
            ],
        ]
    )]
    protected ?File $pdfDocumentFile = null;

    /**
     * The class name of the entity associated with the PDF document.
     */
    #[ORM\Column(name: 'source_entity_class', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $sourceEntityClass = null;

    /**
     * The ID of the entity associated with the PDF document.
     */
    #[ORM\Column(name: 'source_entity_id', type: Types::INTEGER, nullable: true)]
    protected ?int $sourceEntityId = null;

    /**
     * Arbitrary payload data to be passed to the PDF generator.
     */
    #[ORM\Column(name: 'pdf_document_payload', type: Types::JSON, nullable: true)]
    protected ?array $pdfDocumentPayload = null;

    /**
     * PDF options preset name (e.g., default, default_a4, etc.).
     */
    #[ORM\Column(name: 'pdf_options_preset', type: Types::STRING, nullable: false)]
    protected string $pdfOptionsPreset;

    /**
     * The PDF document state (e.g., new, resolved, failed, etc.).
     */
    #[ORM\Column(name: 'pdf_document_state', type: Types::STRING, length: 255, nullable: false)]
    protected string $pdfDocumentState;

    /**
     * The PDF document generation mode, {@see PdfDocumentGenerationMode}.
     */
    #[ORM\Column(name: 'pdf_document_generation_mode', type: Types::STRING, length: 255, nullable: false)]
    protected string $pdfDocumentGenerationMode;

    public function getId(): ?int
    {
        return $this->id;
    }
}
