<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourceHierarchyProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

class ResourceHierarchyProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResourceHierarchyProvider */
    protected $resourceHierarchyProvider;

    protected function setUp()
    {
        $this->resourceHierarchyProvider = new ResourceHierarchyProvider();
    }

    public function testGetParentClassNamesWithoutSuperclasses()
    {
        self::assertEquals(
            [],
            $this->resourceHierarchyProvider->getParentClassNames(Entity\User::class)
        );
    }

    public function testGetParentClassNamesWithSuperclasses()
    {
        self::assertEquals(
            [Entity\User::class],
            $this->resourceHierarchyProvider->getParentClassNames(Entity\UserProfile::class)
        );
    }
}
