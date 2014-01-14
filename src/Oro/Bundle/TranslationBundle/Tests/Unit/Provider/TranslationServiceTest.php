<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;

class TranslationServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $adapter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dumper;

    /** @var TranslationServiceProvider */
    protected $service;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    public function setUp()
    {
        $this->adapter = $this->getMock('Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter', [], [], '', false);
        $this->dumper  = $this->getMock('Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper', [], [], '', false);
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new TranslationServiceProvider($this->adapter, $this->dumper, 'someTestRootDir', $this->em);
    }

    public function tearDown()
    {
        unset($this->adapter, $this->dumper, $this->service);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';

        $this->adapter->expects($this->once())
            ->method('upload')
            ->with($this->isType('array'), $mode);

        $this->service->setAdapter($this->adapter);
        $this->assertEquals($this->adapter, $this->service->getAdapter());

        $this->service->upload($this->getLangFixturesDir(), $mode);
    }

    /**
     * @dataProvider updateDataProvider
     */
    public function testUpdate($isDownloaded, $downloadFileExists)
    {
        $service = $this->getServiceMock(
            ['download', 'upload', 'cleanup'],
            [$this->adapter, $this->dumper, 'someTestRootDir', $this->em]
        );

        $dir = $this->getLangFixturesDir();

        if ($isDownloaded) {
            $mock = $service->expects($this->once())
                ->method('download');

            if ($downloadFileExists) {
                $mock->will(
                    $this->returnCallback(
                        function ($pathToSave) {
                            $tmpDir = dirname($pathToSave);
                            $path = $tmpDir . DIRECTORY_SEPARATOR . 'en';
                            if (!is_dir($path)) {
                                mkdir($path, 0777, true);
                            }
                            touch($path . DIRECTORY_SEPARATOR . 'messages.en.yml');
                            return true;
                        }
                    )
                );
            } else {
                $mock->will($this->returnValue(true));
            }
        } else {
            $service->expects($this->once())
                ->method('download')
                ->will($this->returnValue(false));

            $this->assertFalse($service->update($dir));
            return;
        }

        $service->expects($this->once())
            ->method('upload')
            ->with($this->isType('string'), 'update');

        $service->expects($this->once())
            ->method('cleanup');

        $service->update($dir);
    }

    public function testDownload()
    {
        $service = $this->getServiceMock(
            ['cleanup', 'renameFiles', 'apply', 'unzip'],
            [$this->adapter, $this->dumper, 'someTestRootDir', $this->em]
        );

        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $path = $path . ltrim(uniqid(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'zip';
        mkdir(dirname($path), 0777, true);
        touch($path . TranslationServiceProvider::FILE_NAME_SUFFIX);

        $service->expects($this->exactly(2))
            ->method('cleanup');

        $this->adapter->expects($this->once())
            ->method('download')
            ->will($this->returnValue(true));

        $service->expects($this->once())
            ->method('unzip')
            ->will($this->returnValue(true));

        $service->expects($this->once())
            ->method('renameFiles');

        $service->expects($this->once())
            ->method('apply')
            ->will($this->returnValue(['en']));

        $this->dumper->expects($this->once())
            ->method('dumpTranslations')
            ->with(['en']);

        $service->download($path, ['Oro'], 'en');
    }

    /**
     * Data provider for testUpdate
     */
    public function updateDataProvider()
    {
        return [
            'downloaded ok'       => [true, true],
            'downloaded ok empty' => [true, false],
            'not downloaded'      => [false, false]
        ];
    }

    /**
     * @return string
     */
    protected function getLangFixturesDir()
    {
        return __DIR__ . '/../Fixtures/Resources/lang-pack/';
    }

    protected function getServiceMock($methods = [], $args = [])
    {
        return $this->getMock(
            'Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider',
            $methods,
            $args
        );
    }
}
