<?php

namespace Oro\Component\Action\Tests\Unit\Model;

use Oro\Component\Action\Model\DoctrineTypeMappingExtension;

class DoctrineTypeMappingExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineTypeMappingExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->extension = new DoctrineTypeMappingExtension();
    }

    public function testGetDoctrineTypeMappings()
    {
        $this->extension->addDoctrineTypeMapping('test1', 'test1');
        $this->extension->addDoctrineTypeMapping('test2', 'test2', ['class' => 'test2']);

        $this->assertEquals(
            [
                'test1' => ['type' => 'test1', 'options' => []],
                'test2' => ['type' => 'test2', 'options' => ['class' => 'test2']]
            ],
            $this->extension->getDoctrineTypeMappings()
        );
    }
}
