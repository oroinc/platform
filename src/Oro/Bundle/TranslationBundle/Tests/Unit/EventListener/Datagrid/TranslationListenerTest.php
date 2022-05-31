<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\TranslationListener;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TranslationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new TranslationListener($this->languageProvider, $this->translator);
    }

    public function testOnBuildBefore(): void
    {
        $config = DatagridConfiguration::create([]);
        $datagrid = new Datagrid('test', $config, new ParameterBag());
        self::assertNull($datagrid->getParameters()->get('en_language'));

        $enLanguage = new Language();

        $this->languageProvider->expects(self::once())
            ->method('getDefaultLanguage')
            ->willReturn($enLanguage);

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $config));

        self::assertSame($enLanguage, $datagrid->getParameters()->get('en_language'));
    }

    public function testOnResultAfter(): void
    {
        $record1 = new ResultRecord(['key' => 'key1', 'domain' => 'jsmessages', 'code' => 'en_US']);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('key1', [], 'jsmessages', 'en_US')
            ->willReturn('translated value 1');

        $this->listener->onResultAfter(new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$record1]
        ));

        self::assertEquals('translated value 1', $record1->getValue('current'));
    }
}
