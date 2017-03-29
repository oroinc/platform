<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\CacheWarmer;

use Oro\Bundle\NavigationBundle\CacheWarmer\TitleAnnotationsCacheWarmer;
use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;

class TitleAnnotationsCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AnnotationsReader|\PHPUnit_Framework_MockObject_MockObject */
    protected $reader;

    /** @var TitleAnnotationsCacheWarmer */
    protected $cacheWarmer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->reader = $this->createMock(AnnotationsReader::class);

        $this->cacheWarmer = new TitleAnnotationsCacheWarmer($this->reader);
    }

    public function testWarmUp()
    {
        $this->reader
            ->expects($this->once())
            ->method('getControllerClasses');

        $this->cacheWarmer->warmUp('');
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->cacheWarmer->isOptional());
    }
}
