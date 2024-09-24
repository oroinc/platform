<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class TranslatedEmailTemplateProviderTest extends TestCase
{
    private TranslatedEmailTemplateProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $propertyAccessor = new PropertyAccessor();

        $this->provider = new TranslatedEmailTemplateProvider($propertyAccessor);
    }

    public function testGetTranslatedEmailTemplateWhenNoTranslations(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content');

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        $localization42 = $this->createLocalization(42);

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42)
        );
    }

    public function testGetTranslatedEmailTemplateWhenOneTranslationAndFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42)
        );
    }

    public function testGetTranslatedEmailTemplateWhenOneTranslationAndNotFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Sample subject #42')
            ->setSubjectFallback(false)
            ->setContent('Sample content #42')
            ->setContentFallback(false);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateTranslation1->getSubject())
            ->setContent($emailTemplateTranslation1->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42)
        );
    }

    public function testGetTranslatedEmailTemplateWhenTwoTranslationsAndFirstFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Sample subject #42')
            ->setSubjectFallback(false)
            ->setContent('Sample content #42')
            ->setContentFallback(false);

        $localization43 = $this->createLocalization(43, $localization42);
        $emailTemplateTranslation2 = (new EmailTemplateTranslation())
            ->setLocalization($localization43)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1)
            ->addTranslation($emailTemplateTranslation2);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateTranslation1->getSubject())
            ->setContent($emailTemplateTranslation1->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization43)
        );
    }

    public function testGetTranslatedEmailTemplateWhenTwoTranslationsAndBothFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $localization43 = $this->createLocalization(43, $localization42);
        $emailTemplateTranslation2 = (new EmailTemplateTranslation())
            ->setLocalization($localization43)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1)
            ->addTranslation($emailTemplateTranslation2);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization43)
        );
    }

    public function testGetTranslatedEmailTemplateWhenTwoTranslationsAndRecursiveLocalizations(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $localization43 = $this->createLocalization(43, $localization42);

        // Causes infinite recursion of localizations.
        $localization42->setParentLocalization($localization43);

        $emailTemplateTranslation2 = (new EmailTemplateTranslation())
            ->setLocalization($localization43)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1)
            ->addTranslation($emailTemplateTranslation2);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization43)
        );
    }

    private function createLocalization(int $id, Localization $parentLocalization = null): Localization
    {
        $localization = new LocalizationStub($id);
        if ($parentLocalization !== null) {
            $localization->setParentLocalization($parentLocalization);
        }

        return $localization;
    }
}
