<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationAdaptersCollection;
use Oro\Bundle\TranslationBundle\Provider\APIAdapterInterface;

class TranslationAdaptersCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationAdaptersCollection */
    private $adaptersCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->adaptersCollection = new TranslationAdaptersCollection();
    }

    public function testCollectionAccessors()
    {
        /** @var $adapter1 APIAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $adapter1 = $this->createMock(APIAdapterInterface::class);
        /** @var $adapter2 APIAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $adapter2 = $this->createMock(APIAdapterInterface::class);

        $this->adaptersCollection->addAdapter($adapter1, 'adapter1');
        $this->adaptersCollection->addAdapter($adapter2, 'adapter2');

        $this->assertSame($adapter1, $this->adaptersCollection->getAdapter('adapter1'));
        $this->assertSame($adapter2, $this->adaptersCollection->getAdapter('adapter2'));
    }
}
