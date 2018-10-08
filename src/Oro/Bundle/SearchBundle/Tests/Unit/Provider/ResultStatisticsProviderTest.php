<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;

class ResultStatisticsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResultStatisticsProvider
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $indexer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $search;

    protected function setUp()
    {
        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->search = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result')
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexer->expects($this->any())
            ->method('simpleSearch')
            ->will($this->returnValue($this->search));

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())->method('hasConfig')->will($this->returnValue(true));

        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ResultStatisticsProvider($this->indexer, $this->configManager, $this->translator);
    }

    public function testGetResults()
    {
        $query = 'test query';
        $this->indexer->expects($this->once())->method('simpleSearch')->with($query);
        $this->target->getResults($query);
    }

    public function testGetGroupedResults()
    {
        $expectedString = 'expected';

        $firstClass = 'firstClass';
        $firstConfig = array('alias' => $firstClass);
        $firstLabel = 'first label';
        $firstIcon = 'first icon';

        $firstConfigEntity = $this->getConfigEntity($firstLabel, $firstIcon);
        $first = $this->getSearchResultEntity($firstConfig, $firstClass);

        $secondClass = 'secondClass';
        $secondConfig = array('alias' => $secondClass);
        $secondLabel = 'second label';
        $secondIcon = 'second icon';
        $second = $this->getSearchResultEntity($secondConfig, $secondClass);
        $secondConfigEntity = $this->getConfigEntity($secondLabel, $secondIcon);
        $map = array($firstClass => $firstConfigEntity, $secondClass => $secondConfigEntity);
        $this->configManager
            ->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function (EntityConfigId $entityConfigId) use ($map) {
                        return $map[$entityConfigId->getClassName()];
                    }
                )
            );

        $elements = array($second, $first, $first);


        $expected = array(
            ''           => array(
                'count'  => 3,
                'class'  => '',
                'config' => array(),
                'label'  => '',
                'icon'   => ''
            ),
            $firstClass  => array(
                'count'  => 2,
                'class'  => $firstClass,
                'config' => $firstConfig,
                'label'  => $firstLabel,
                'icon'   => $firstIcon
            ),
            $secondClass => array(
                'count'  => 1,
                'class'  => $secondClass,
                'config' => $secondConfig,
                'label'  => $secondLabel,
                'icon'   => $secondIcon
            )
        );

        $this->search->expects($this->once())
            ->method('getElements')
            ->will($this->returnValue($elements));

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->will($this->returnArgument(0));
        $actual = $this->target->getGroupedResults($expectedString);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array $config
     * @param string $class
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSearchResultEntity(array $config, $class)
    {
        $entity = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->any())
            ->method('getEntityConfig')
            ->will($this->returnValue($config));
        $entity->expects($this->any())
            ->method('getEntityName')
            ->will($this->returnValue($class));

        return $entity;
    }

    /**
     * @param string $label
     * @param string $icon
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigEntity($label, $icon)
    {
        $configEntity = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configEntity->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnValue(true));
        $configEntity->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('plural_label', false, null, $label),
                        array('icon', false, null, $icon)
                    )
                )
            );
        return $configEntity;
    }
}
