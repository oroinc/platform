<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decimal entity for search index
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_search_index_decimal')]
#[ORM\Index(columns: ['field'], name: 'oro_search_index_decimal_field_idx')]
#[ORM\Index(columns: ['item_id', 'field'], name: 'oro_search_index_decimal_item_field_idx')]
class IndexDecimal extends AbstractIndexDecimal
{
    #[ORM\ManyToOne(targetEntity: 'Item', inversedBy: 'decimalFields')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Item $item = null;
}
