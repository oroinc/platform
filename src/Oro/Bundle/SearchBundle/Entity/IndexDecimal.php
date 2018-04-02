<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decimal entity for search index
 *
 * @ORM\Table(
 *      name="oro_search_index_decimal",
 *      indexes={
 *          @ORM\Index(name="oro_search_index_decimal_field_idx", columns={"field"}),
 *          @ORM\Index(name="oro_search_index_decimal_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexDecimal extends AbstractIndexDecimal
{
    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SearchBundle\Entity\Item", inversedBy="decimalFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
