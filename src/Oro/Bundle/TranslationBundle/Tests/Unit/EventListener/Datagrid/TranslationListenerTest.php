<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\TranslationListener;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class TranslationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    /** @var TranslationListener */
    protected $listener;

    protected function setUp()
    {
        $this->provider = $this->createMock(LanguageProvider::class);
        $this->listener = new TranslationListener($this->provider);
    }

    public function testOnBuildBefore()
    {
        $language = new Language();
        $event = $this->getEvent();

        $this->provider->expects($this->once())->method('getDefaultLanguage')->willReturn($language);

        $this->assertNull($event->getDatagrid()->getParameters()->get(TranslationListener::PARAM));

        $this->listener->onBuildBefore($event);

        $this->assertSame($language, $event->getDatagrid()->getParameters()->get(TranslationListener::PARAM));
    }

    /**
     * @return BuildBefore
     */
    protected function getEvent()
    {
        $config = DatagridConfiguration::create([]);
        $datagrid = new Datagrid('test', $config, new ParameterBag());

        return new BuildBefore($datagrid, $config);
    }
}
