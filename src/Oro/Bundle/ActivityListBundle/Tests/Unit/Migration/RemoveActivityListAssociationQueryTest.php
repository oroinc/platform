<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Migration;

use Oro\Bundle\ActivityListBundle\Migration\RemoveActivityListAssociationQuery;

class RemoveActivityListAssociationQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialized()
    {
        $query = new RemoveActivityListAssociationQuery('Some\Entity', true);
        self::assertEquals(
            'Remove association relation from Oro\Bundle\ActivityListBundle\Entity\ActivityList entity to Some\Entity '
            . '(association kind: activityList, relation type: manyToMany, drop relation column/table: yes, '
            . 'source table: n/a, target table: n/a).',
            $query->getDescription()
        );
    }
}
