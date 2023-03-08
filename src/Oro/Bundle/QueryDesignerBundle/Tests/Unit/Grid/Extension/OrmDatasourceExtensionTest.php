<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberFilter;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;
use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\DateTimeRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Oro\Bundle\LocaleBundle\Formatter\Factory\IntlNumberFormatterFactory;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\OrmDatasourceExtension;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrmDatasourceExtensionTest extends OrmTestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var FormFactoryInterface */
    private $formFactory;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->localeSettings->expects(self::any())
            ->method('getTimeZone')
            ->willReturn('America/Los_Angeles');
        $this->localeSettings->expects(self::any())
            ->method('getTimeZone')
            ->willReturn('UTC');
        $this->localeSettings->expects(self::any())
            ->method('getLocale')
            ->willReturn('en');

        $subscriber = new MutableFormEventSubscriber($this->createMock(DateFilterSubscriber::class));

        $numberFormatter = new NumberFormatter(
            $this->localeSettings,
            new IntlNumberFormatterFactory($this->localeSettings)
        );

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([
                new PreloadedExtension(
                    [
                        'oro_type_text_filter'           => new TextFilterType($translator),
                        'oro_type_number_filter'         => new NumberFilterType(
                            $translator,
                            $numberFormatter
                        ),
                        'oro_type_datetime_range_filter' => new DateTimeRangeFilterType(
                            $translator,
                            new DateModifierProvider(),
                            $subscriber
                        ),
                        'oro_type_date_range_filter'     => new DateRangeFilterType(
                            $translator,
                            new DateModifierProvider(),
                            $subscriber
                        ),
                        'oro_type_datetime_range'        => new DateTimeRangeType(),
                        'oro_type_date_range'            => new DateRangeType($this->localeSettings),
                        'oro_type_filter'                => new FilterType($translator),
                    ],
                    []
                ),
                new CsrfExtension(
                    $this->createMock(CsrfTokenManagerInterface::class)
                )
            ])
            ->getFormFactory();
    }

    /**
     * @dataProvider visitDatasourceProvider
     */
    public function testVisitDatasource(array $source, string $expected, bool $enableGrouping = false): void
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['user.id', 'user.name as user_name', 'user.status as user_status'])
            ->from(CmsUser::class, 'user')
            ->join('user.address', 'address')
            ->join('user.shippingAddresses', 'shippingAddresses');

        $manager = $this->createMock(Manager::class);
        $manager->expects(self::any())
            ->method('createFilter')
            ->willReturnCallback(function ($name, $params) {
                return $this->createFilter($name, $params);
            });

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->with('oro_query_designer.conditions_group_merge_same_entity_conditions')
            ->willReturn($enableGrouping);

        $filterExecutionContext = new FilterExecutionContext();
        $filterExecutionContext->enableValidation();
        $extension = new OrmDatasourceExtension(
            new RestrictionBuilder($manager, $configManager, $filterExecutionContext)
        );

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $config = DatagridConfiguration::create($source);
        $config->setName('test_grid');

        $extension->visitDatasource($config, $datasource);
        $result = $qb->getDQL();
        $counter = 0;
        $result = preg_replace_callback(
            '/(:[a-z]+)(\d+)/',
            function ($matches) use (&$counter) {
                return $matches[1] . (++$counter);
            },
            $result
        );

        $expected = str_replace([PHP_EOL, '( ', ' )'], [' ', '(', ')'], preg_replace('/\s+/', ' ', $expected));
        // Check that generated DQL is valid and may be converted to SQL
        self::assertNotEmpty($qb->getQuery()->getSQL());
        // Check that generated DQL is expected
        self::assertEquals($expected, $result);
    }

    public function visitDatasourceProvider(): array
    {
        return Yaml::parse(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'orm_datasource_data.yml']))
        );
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string $name   A filter name
     * @param array  $params An additional parameters of a new filter
     *
     * @return FilterInterface
     */
    private function createFilter(string $name, array $params = null): FilterInterface
    {
        $defaultParams = [
            'type' => $name
        ];
        if ($params !== null && !empty($params)) {
            $params = array_merge($defaultParams, $params);
        }
        $util = new FilterUtility();

        switch ($name) {
            case 'string':
                $filter = new StringFilter($this->formFactory, $util);
                break;
            case 'number':
                $filter = new NumberFilter($this->formFactory, $util);
                break;
            case 'datetime':
                $compiler = $this->createMock(Compiler::class);
                $filter = new DateTimeRangeFilter(
                    $this->formFactory,
                    $util,
                    new DateFilterUtility($this->localeSettings, $compiler),
                    $this->localeSettings,
                    new DateFilterModifier($compiler)
                );
                break;
            default:
                throw new \Exception(sprintf('Not implementer in this test filter: "%s".', $name));
        }
        $filter->init($name, $params);

        return $filter;
    }
}
