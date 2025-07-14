<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TranslationHelperTest extends TestCase
{
    use EntityTrait;

    private TranslationRepository&MockObject $repository;
    private TranslationHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(TranslationRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($em);

        $this->helper = new TranslationHelper($doctrine);
    }

    public function testFindValues(): void
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

    public function testFindValue(): void
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

    public function testFindValueWithoutTranslation(): void
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
