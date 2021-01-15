<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Provider\FileTitleProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class FileTitleProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileTitleProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerFileTitleProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FileTitleProvider */
    private $provider;

    /** @var File|\PHPUnit\Framework\MockObject\MockObject */
    private $file;

    protected function setUp(): void
    {
        $this->innerFileTitleProvider = $this->createMock(FileTitleProviderInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new FileTitleProvider(
            $this->innerFileTitleProvider,
            $this->localizationHelper,
            $this->doctrine
        );

        $this->file = $this->getMockBuilder(File::class)
            ->addMethods(['getDigitalAsset'])
            ->getMock();
    }

    public function testGetTitleWhenNoDigitalAssetNoParent(): void
    {
        $this->innerFileTitleProvider
            ->expects($this->once())
            ->method('getTitle')
            ->with($this->file)
            ->willReturn($title = 'sample title');

        $this->assertEquals($title, $this->provider->getTitle($this->file));
    }

    public function testGetTitleWhenNoDigitalAssetNoParentButLocalization(): void
    {
        $this->innerFileTitleProvider
            ->expects($this->once())
            ->method('getTitle')
            ->with($this->file, $localization = $this->createMock(Localization::class))
            ->willReturn($title = 'sample title');

        $this->assertEquals($title, $this->provider->getTitle($this->file, $localization));
    }

    public function testGetTitleWhenNoDigitalAssetButParent(): void
    {
        $this->file
            ->setParentEntityClass(DigitalAsset::class)
            ->setParentEntityId($parentEntityId = 10);

        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(DigitalAsset::class)
            ->willReturn($repo = $this->createMock(EntityRepository::class));

        $repo
            ->expects($this->once())
            ->method('find')
            ->with($parentEntityId)
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $digitalAsset
            ->expects($this->once())
            ->method('getTitles')
            ->willReturn($titles = $this->createMock(Collection::class));

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($titles)
            ->willReturn($title = 'sample title');

        $this->assertEquals($title, $this->provider->getTitle($this->file));
    }

    public function testGetTitleWhenHasDigitalAsset(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $digitalAsset
            ->expects($this->once())
            ->method('getTitles')
            ->willReturn($titles = $this->createMock(Collection::class));

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($titles)
            ->willReturn($title = 'sample title');

        $this->assertEquals($title, $this->provider->getTitle($this->file));
    }

    public function testGetTitleWhenHasDigitalAssetAndLocalization(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $digitalAsset
            ->expects($this->once())
            ->method('getTitles')
            ->willReturn($titles = $this->createMock(Collection::class));

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($titles)
            ->willReturn($title = 'sample title', $localization = $this->createMock(Localization::class));

        $this->assertEquals($title, $this->provider->getTitle($this->file, $localization));
    }
}
