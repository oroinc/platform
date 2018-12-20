<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class TranslationHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var TranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var TranslationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(TranslationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->repository);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($manager);

        $this->helper = new TranslationHelper($this->registry);
    }

    public function testFindValues()
    {
        $keysPrefix = 'oro.trans';
        $locale = 'en';
        $domain = 'messages';
        $data = ['data'];

        $this->repository->expects($this->once())
            ->method('findValues')
            ->with($keysPrefix, $locale, $domain)
            ->willReturn($data);

        $this->assertEquals($data, $this->helper->findValues($keysPrefix, $locale, $domain));
    }

    public function testFindValue()
    {
        $key = 'oro.trans.test.key';
        $locale = 'en';
        $domain = 'messages';
        $data = (new Translation())->setValue('test_value');

        $this->repository->expects($this->once())
            ->method('findTranslation')
            ->with($key, $locale, $domain)
            ->willReturn($data);

        $this->assertEquals($data->getValue(), $this->helper->findValue($key, $locale, $domain));
    }

    public function testFindValueWithoutTranslation()
    {
        $key = 'oro.trans.test.key';
        $locale = 'en';
        $domain = 'messages';

        $this->repository->expects($this->once())
            ->method('findTranslation')
            ->with($key, $locale, $domain)
            ->willReturn(null);

        $this->assertNull($this->helper->findValue($key, $locale, $domain));
    }
}
