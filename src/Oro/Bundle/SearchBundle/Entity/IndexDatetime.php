<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Date time entity for search index
 *
 * @ORM\Table(name="oro_search_index_datetime")
 * @ORM\Entity
 */
class IndexDatetime extends AbstractIndexDatetime
{
}
