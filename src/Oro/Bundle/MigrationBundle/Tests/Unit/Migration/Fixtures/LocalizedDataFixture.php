<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\DataFixtures\LocalizationOptionsAwareInterface;
use Oro\Bundle\LocaleBundle\DataFixtures\LocalizationOptionsAwareTrait;

class LocalizedDataFixture extends AbstractFixture implements LocalizationOptionsAwareInterface
{
    use LocalizationOptionsAwareTrait;

    public function getFormattingCode(): string
    {
        return $this->formattingCode;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function load(ObjectManager $manager): void
    {
        // do nothing
    }
}
