<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decimal entity for search index
 *
 * @ORM\Table(name="oro_search_index_decimal")
 * @ORM\Entity
 */
class IndexDecimal extends AbstractIndexDecimal
{
}
