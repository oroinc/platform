<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class DigitalAssetTest extends \PHPUnit\Framework\TestCase
{
    /** @var DigitalAsset */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new DigitalAsset();
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(Collection::class, $this->entity->getTitles());
    }

    public function testClone(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        clone $this->entity;
    }

    public function testTitleAccessors(): void
    {
        $this->assertEmpty($this->entity->getTitles()->toArray());

        $firstTitle = $this->createLocalizedValue();
        $secondTitle = $this->createLocalizedValue();

        $this->entity
            ->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);

        $this->assertCount(2, $this->entity->getTitles()->toArray());

        $this->assertEquals([$firstTitle, $secondTitle], array_values($this->entity->getTitles()->toArray()));

        $this->entity
            ->removeTitle($firstTitle)
            ->removeTitle($firstTitle);

        $this->assertEquals([$secondTitle], array_values($this->entity->getTitles()->toArray()));
    }

    private function createLocalizedValue(bool $default = false): LocalizedFallbackValue
    {
        $localized = (new LocalizedFallbackValue())->setString('sample_title');

        if (!$default) {
            $localized->setLocalization(new Localization());
        }

        return $localized;
    }

    public function testSourceFileAccessors(): void
    {
        $this->assertNull($this->entity->getSourceFile());

        $this->entity->setSourceFile($file = new File());

        $this->assertSame($file, $this->entity->getSourceFile());
    }

    public function testCreatedAtAccessors(): void
    {
        $this->assertNull($this->entity->getCreatedAt());

        $this->entity->setCreatedAt($dateTime = new \DateTime());

        $this->assertSame($dateTime, $this->entity->getCreatedAt());
    }

    public function testUpdatedAtAccessors(): void
    {
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->setUpdatedAt($dateTime = new \DateTime());

        $this->assertSame($dateTime, $this->entity->getUpdatedAt());
    }

    public function testPrePersist(): void
    {
        $this->assertNull($this->entity->getCreatedAt());
        $this->entity->prePersist();
        $this->assertInstanceOf(\DateTime::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->entity->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $this->assertNull($this->entity->getUpdatedAt());
        $this->entity->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->entity->getUpdatedAt());
    }
}
