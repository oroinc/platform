<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;
use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\DateTimeRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\QueryDesignerBundle\Filter\ConditionsGroupFilter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs\OrmDatasourceExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrmDatasourceExtensionTest extends OrmTestCase
{
    /** @var FormFactoryInterface */
    private $formFactory;

    protected function setUp(): void
    {
        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())->method('trans')->will($this->returnArgument(0));

        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('America/Los_Angeles');

        $subscriber = new MutableFormEventSubscriber($this->createMock(DateFilterSubscriber::class));

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                [
                    new PreloadedExtension(
                        [
                            'oro_type_text_filter' => new TextFilterType($translator),
                            'oro_type_datetime_range_filter' =>
                                new DateTimeRangeFilterType($translator, new DateModifierProvider(), $subscriber),
                            'oro_type_date_range_filter' =>
                                new DateRangeFilterType($translator, new DateModifierProvider(), $subscriber),
                            'oro_type_datetime_range' => new DateTimeRangeType($localeSettings),
                            'oro_type_date_range' => new DateRangeType($localeSettings),
                            'oro_type_filter' => new FilterType($translator),
                        ],
                        []
                    ),
                    new CsrfExtension(
                        $this->createMock(CsrfTokenManagerInterface::class)
                    )
                ]
            )
            ->getFormFactory();
    }

    /**
     * @dataProvider visitDatasourceProvider
     * @param array $source
     * @param string $expected
     * @param bool $enableGrouping
     */
    public function testVisitDatasource($source, $expected, $enableGrouping = false)
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['user.id', 'user.name as user_name', 'user.status as user_status'])
            ->from(CmsUser::class, 'user')
            ->join('user.address', 'address')
            ->join('user.shippingAddresses', 'shippingAddresses');

        /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->any())
            ->method('createFilter')
            ->will(
                $this->returnCallback(
                    function ($name, $params) {
                        return $this->createFilter($name, $params);
                    }
                )
            );

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_query_designer.conditions_group_merge_same_entity_conditions')
            ->willReturn($enableGrouping);
        $restrictionBuilder = new RestrictionBuilder($manager, $configManager);
        $conditionsGroupFilter = new ConditionsGroupFilter($restrictionBuilder);
        $manager->expects($this->any())
            ->method('getFilter')
            ->with('conditions_group')
            ->willReturn($conditionsGroupFilter);

        $extension = new OrmDatasourceExtension($restrictionBuilder);
        /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject $datasource */
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

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
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function visitDatasourceProvider()
    {
        return Yaml::parse(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'orm_datasource_data.yml']))
        );
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string $name A filter name
     * @param array $params An additional parameters of a new filter
     *
     * @return FilterInterface
     * @throws \Exception
     */
    public function createFilter($name, array $params = null)
    {
        $defaultParams = [
            'type' => $name
        ];
        if ($params !== null && !empty($params)) {
            $params = array_merge($defaultParams, $params);
        }

        switch ($name) {
            case 'string':
                $filter = new StringFilter($this->formFactory, new FilterUtility());
                break;
            case 'datetime':
                $localeSetting = $this->createMock(LocaleSettings::class);
                $localeSetting->expects($this->any())
                    ->method('getTimeZone')
                    ->will($this->returnValue('UTC'));
                $compiler = $this->createMock(Compiler::class);

                $filter = new DateTimeRangeFilter(
                    $this->formFactory,
                    new FilterUtility(),
                    new DateFilterUtility($localeSetting, $compiler)
                );
                break;
            default:
                throw new \Exception(sprintf('Not implementer in this test filter: "%s".', $name));
        }
        $filter->init($name, $params);

        return $filter;
    }
}
