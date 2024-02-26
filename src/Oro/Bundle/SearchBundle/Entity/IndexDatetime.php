<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Date time entity for search index
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_search_index_datetime')]
#[ORM\Index(columns: ['field'], name: 'oro_search_index_datetime_field_idx')]
#[ORM\Index(columns: ['item_id', 'field'], name: 'oro_search_index_datetime_item_field_idx')]
class IndexDatetime extends AbstractIndexDatetime
{
    #[ORM\ManyToOne(targetEntity: 'Item', inversedBy: 'datetimeFields')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Item $item = null;
}
