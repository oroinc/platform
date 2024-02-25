<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Integer entity for search index
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_search_index_integer')]
#[ORM\Index(columns: ['field'], name: 'oro_search_index_integer_field_idx')]
#[ORM\Index(columns: ['item_id', 'field'], name: 'oro_search_index_integer_item_field_idx')]
class IndexInteger extends AbstractIndexInteger
{
    #[ORM\ManyToOne(targetEntity: 'Item', inversedBy: 'integerFields')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Item $item = null;
}
