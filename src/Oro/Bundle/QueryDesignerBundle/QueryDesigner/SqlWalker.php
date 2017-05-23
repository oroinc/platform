<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

class SqlWalker extends TranslationWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        $hints = $this->getQuery()->getHints();
        $query = $this->getQuery();
        $sql = parent::walkSubselect($subselect);

        //$sql = "SELECT customTable.id FROM ($sql LIMIT 2) customTable";
        $sql = "SELECT customTable.id FROM ($sql LIMIT 2) customTable";
       // $sql = "SELECT op2.id1 FROM oro_product op2	JOIN (SELECT op.id, op.sku FROM oro_product op WHERE op.sku LIKE '%1%' 	ORDER BY op.sku DESC LIMIT 2) as result_table ON result_table.id=op2.id";
        return $sql;
    }
}
