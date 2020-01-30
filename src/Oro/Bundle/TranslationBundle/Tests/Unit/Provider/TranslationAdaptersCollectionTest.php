<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\APIAdapterInterface;
use Oro\Bundle\TranslationBundle\Provider\TranslationAdaptersCollection;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class TranslationAdaptersCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAdapter()
    {
        $adapter1 = $this->createMock(APIAdapterInterface::class);
        $adapter2 = $this->createMock(APIAdapterInterface::class);

        $container = TestContainerBuilder::create()
            ->add('adapter1', $adapter1)
            ->add('adapter2', $adapter2)
            ->getContainer($this);

        $adaptersCollection = new TranslationAdaptersCollection($container);

        $this->assertSame($adapter1, $adaptersCollection->getAdapter('adapter1'));
        $this->assertSame($adapter2, $adaptersCollection->getAdapter('adapter2'));
        $this->assertNull($adaptersCollection->getAdapter('another'));
    }
}
