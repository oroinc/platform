<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Text entity for search index
 * @ORM\Table(
 *      name="oro_search_index_text",
 *      indexes={
 *          @ORM\Index(name="oro_search_index_text_field_idx", columns={"field"}),
 *          @ORM\Index(name="oro_search_index_text_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexText extends AbstractIndexText
{
    const HYPHEN_SUBSTITUTION = ' ';
    const TABLE_NAME = 'oro_search_index_text';
}
