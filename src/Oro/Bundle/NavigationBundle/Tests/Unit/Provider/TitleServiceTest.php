<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Provider\TitleService;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TitleServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $annotationsReader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configReader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleTranslator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $breadcrumbManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userConfigManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleProvider;

    /**
     * @var TitleService
     */
    private $titleService;

    protected function setUp()
    {
        $this->annotationsReader =
            $this->getMockBuilder('Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader')
                ->disableOriginalConstructor()
                ->getMock();

        $this->configReader = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Title\TitleReader\ConfigReader')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->titleTranslator = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userConfigManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->breadcrumbManager = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->titleProvider = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $breadcrumbLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $breadcrumbLink->expects($this->any())->method('getService')->willReturn($this->breadcrumbManager);

        $this->titleService = new TitleService(
            $this->annotationsReader,
            $this->configReader,
            $this->titleTranslator,
            $this->em,
            $this->userConfigManager,
            $breadcrumbLink,
            $this->titleProvider
        );
    }

    public function testRender()
    {
        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('PrefixSuffix', [])
            ->will($this->returnValue('PrefixSuffix'));

        $result = $this->titleService->render(array(), null, 'Prefix', 'Suffix');

        $this->assertTrue(is_string($result));
    }

    public function testRenderStored()
    {
        $data = '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"},'
            . '"prefix":"test prefix","suffix":"test suffix"}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('test prefixtest templatetest suffix', ['prm1' => 'val1'])
            ->will($this->returnValue('translated template'));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('translated template', $result);
    }

    public function testRenderStoredForShortTemplate()
    {
        $data = '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"},'
            . '"prefix":"test prefix","suffix":"test suffix"}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('test short template', ['prm1' => 'val1'])
            ->will($this->returnValue('translated short template'));

        $result = $this->titleService->render([], $data, null, null, true, true);

        $this->assertEquals('translated short template', $result);
    }

    public function testRenderStoredWithoutOptionalData()
    {
        $data = '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"}}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('test template', ['prm1' => 'val1'])
            ->will($this->returnValue('translated template'));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('translated template', $result);
    }

    public function testRenderStoredWithEmptyData()
    {
        $data = '{"template":null,"short_template":null,"params":[]}';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('', [])
            ->will($this->returnValue(''));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('', $result);
    }

    public function testRenderStoredInvalidData()
    {
        $data = 'invalid';

        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with('Untitled', [])
            ->will($this->returnValue('translated Untitled'));

        $result = $this->titleService->render([], $data, null, null, true);

        $this->assertEquals('translated Untitled', $result);
    }

    public function testRenderShort()
    {
        $shortTitle = 'short title';
        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with($shortTitle, [])
            ->will($this->returnValue($shortTitle));
        $this->titleService->setShortTemplate($shortTitle);
        $result = $this->titleService->render(array(), null, 'Prefix', 'Suffix', true, true);
        $this->assertTrue(is_string($result));
        $this->assertEquals($result, $shortTitle);
    }

    public function testSettersAndGetters()
    {
        $testString = 'Test string';
        $testArray = array('test');

        $this->assertInstanceOf(
            '\Oro\Bundle\NavigationBundle\Provider\TitleService',
            $this->titleService->setSuffix($testString)
        );
        $this->assertInstanceOf(
            '\Oro\Bundle\NavigationBundle\Provider\TitleService',
            $this->titleService->setPrefix($testString)
        );

        $this->titleService->setParams($testArray);
        $this->assertEquals($testArray, $this->titleService->getParams());

        $dataArray = array(
            'titleTemplate' => 'titleTemplate',
            'titleShortTemplate' => 'titleShortTemplate',
            'prefix' => 'prefix',
            'suffix' => 'suffix',
            'params' => array('test_params')
        );
        $this->titleService->setData($dataArray);

        $this->assertEquals($dataArray['titleTemplate'], $this->titleService->getTemplate());
        $this->assertEquals($dataArray['titleShortTemplate'], $this->titleService->getShortTemplate());
        $this->assertEquals($dataArray['params'], $this->titleService->getParams());
    }

    public function testLoadByRoute()
    {
        $route          = 'test_route';
        $testTitle      = 'Test title';
        $testShortTitle = 'Test short title';

        $this->titleProvider->expects($this->once())
            ->method('getTitleTemplates')
            ->with($route)
            ->will($this->returnValue(['title' => $testTitle, 'short_title' => $testShortTitle]));

        $this->titleService->loadByRoute($route);

        $this->assertEquals($testTitle, $this->titleService->getTemplate());
        $this->assertEquals($testShortTitle, $this->titleService->getShortTemplate());
    }

    public function testLoadByRouteWhenTitleDoesNotExist()
    {
        $route = 'test_route';

        $this->titleProvider->expects($this->once())
            ->method('getTitleTemplates')
            ->with($route)
            ->will($this->returnValue([]));

        $this->titleService->loadByRoute($route);

        $this->assertNull($this->titleService->getTemplate());
        $this->assertNull($this->titleService->getShortTemplate());
    }

    /**
     * Prepare readers for update test
     */
    private function prepareReaders()
    {
        $this->annotationsReader->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array()));

        $this->configReader->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array()));
    }

    public function testRemoveItemsDuringUpdate()
    {
        $this->prepareReaders();

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $entityMock = $this->getMock('Oro\Bundle\NavigationBundle\Entity\Title');

        $entityMock->expects($this->once())
            ->method('getRoute')
            ->will($this->returnValue('test_route'));

        $this->repository->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array($entityMock)));

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->titleService->update(array());
    }

    public function testUpdateItemsDuringUpdate()
    {
        $this->prepareReaders();

        $testData = array('route_name' => 'Title');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->userConfigManager->expects($this->at(0))->method('get')
            ->with('oro_navigation.breadcrumb_menu')->will($this->returnValue('test-menu'));
        $this->userConfigManager->expects($this->at(1))->method('get')
            ->with('oro_navigation.title_suffix')->will($this->returnValue('test-suffix'));
        $this->userConfigManager->expects($this->at(2))->method('get')
            ->with('oro_navigation.title_delimiter')->will($this->returnValue('/'));

        $entityMock = $this->getMock('Oro\Bundle\NavigationBundle\Entity\Title');

        $entityMock->expects($this->exactly(2))
            ->method('getRoute')
            ->will($this->returnValue('route_name'));

        $entityMock->expects($this->once())
            ->method('getIsSystem')
            ->will($this->returnValue(true));

        $entityMock->expects($this->once())
            ->method('setTitle')
            ->with($this->equalTo('Title / test-breadcrumb / test-suffix'));

        $entityMock->expects($this->once())
            ->method('setShortTitle')
            ->with($this->equalTo('Title'));

        $this->repository->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array($entityMock)));

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbLabels')
            ->will($this->returnValue(array('test-breadcrumb')));

        $this->titleService->update($testData);
    }

    public function testInsertItemsDuringUpdate()
    {
        $this->prepareReaders();

        $testData = array('route_name' => 'Title');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->repository->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array()));

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\NavigationBundle\Entity\Title'));

        $this->em->expects($this->once())
            ->method('flush');

        $this->titleService->update($testData);
    }

    public function testGetSerialized()
    {
        $this->titleService->setTemplate('test template');
        $this->titleService->setShortTemplate('test short template');
        $this->titleService->setParams(['prm1' => 'val1']);
        $this->titleService->setPrefix('test prefix');
        $this->titleService->setSuffix('test suffix');

        $this->assertEquals(
            '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"},'
            . '"prefix":"test prefix","suffix":"test suffix"}',
            $this->titleService->getSerialized()
        );
    }

    public function testGetSerializedWithoutOptionalData()
    {
        $this->titleService->setTemplate('test template');
        $this->titleService->setShortTemplate('test short template');
        $this->titleService->setParams(['prm1' => 'val1']);

        $this->assertEquals(
            '{"template":"test template","short_template":"test short template","params":{"prm1":"val1"}}',
            $this->titleService->getSerialized()
        );
    }

    public function testGetSerializedWithEmptyData()
    {
        $this->assertEquals(
            '{"template":null,"short_template":null,"params":[]}',
            $this->titleService->getSerialized()
        );
    }
}
