<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Date time entity for search index
 *
 * @ORM\Table(
 *      name="oro_search_index_datetime",
 *      indexes={
 *          @ORM\Index(name="oro_search_index_datetime_field_idx", columns={"field"}),
 *          @ORM\Index(name="oro_search_index_datetime_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexDatetime extends AbstractIndexDatetime
{
    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SearchBundle\Entity\Item", inversedBy="datetimeFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
