<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Search;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\SearchBundle\Tests\Functional\Engine\AbstractEntitiesOrmIndexerTest;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Item2;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\ThemeBundle\Entity\Enum\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Tests that Platform entities can be indexed without type casting errors with the ORM search engine.
 *
 * @group search
 * @dbIsolationPerTest
 */
class PlatformEntitiesOrmIndexerTest extends AbstractEntitiesOrmIndexerTest
{
    #[\Override]
    protected function getSearchableEntityClassesToTest(): array
    {
        return [
            BusinessUnit::class,
            Email::class,
            EmailUser::class,
            Group::class,
            Item::class,
            Item2::class,
            Localization::class,
            Organization::class,
            Product::class,
            Report::class,
            Role::class,
            Tag::class,
            Taxonomy::class,
            TestProduct::class,
            ThemeConfiguration::class,
            User::class,
        ];
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOrganization::class, LoadUser::class]);

        $manager = $this->getDoctrine()->getManagerForClass(User::class);
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $businessUnit = $user->getOwner();

        $testBusinessUnit = (new BusinessUnit())
            ->setOrganization($organization)
            ->setName('Test Business Unit');
        $this->persistTestEntity($testBusinessUnit);

        $role = (new Role())
            ->setLabel('Test Role')
            ->setRole('ROLE_TEST');
        $this->persistTestEntity($role);

        $group = (new Group())
            ->setName('Test Group')
            ->setOrganization($organization);
        $this->persistTestEntity($group);

        $tag = (new Tag())
            ->setOwner($user)
            ->setOrganization($organization)
            ->setName('Test Tag');
        $this->persistTestEntity($tag);

        $taxonomy = (new Taxonomy())
            ->setOwner($user)
            ->setOrganization($organization)
            ->setName('Test Taxonomy');
        $this->persistTestEntity($taxonomy);

        $language = $manager->getRepository(Language::class)->findOneBy(['code' => 'en']);
        if (!$language) {
            $language = (new Language())
                ->setOrganization($organization)
                ->setCode('en');
            $manager->persist($language);
        }

        $localization = (new Localization())
            ->setName('Test Localization')
            ->setLanguage($language)
            ->setFormattingCode('en');
        $manager->persist($localization);
        $this->persistTestEntity($localization);

        $reportType = $manager->getRepository(ReportType::class)
            ->findOneBy(['name' => ReportType::TYPE_TABLE]);

        $report = (new Report())
            ->setOwner($businessUnit)
            ->setOrganization($organization)
            ->setName('Test Report')
            ->setEntity('Oro\Bundle\UserBundle\Entity\User')
            ->setType($reportType)
            ->setDefinition('{}')
            ->setDescription('Test report description');
        $this->persistTestEntity($report);

        $testProduct = new TestProduct();
        $testProduct->setName('Test Product');
        $this->persistTestEntity($testProduct);

        $themeConfiguration = (new ThemeConfiguration())
            ->setOwner($businessUnit)
            ->setOrganization($organization)
            ->setName('Test Theme')
            ->setDescription('Test theme description')
            ->setTheme('default')
            ->setType(ThemeConfigurationType::Storefront);
        $this->persistTestEntity($themeConfiguration);

        $emailAddressManager = self::getContainer()->get('oro_email.email.address.manager');
        $emailAddress = $emailAddressManager->newEmailAddress();
        $emailAddress->setEmail('test@example.com');
        $emailAddressManager->getEntityManager()->persist($emailAddress);

        $email = (new Email())
            ->setSubject('Test Email')
            ->setFromName('Test Sender')
            ->setFromEmailAddress($emailAddress)
            ->setSentAt(new \DateTime())
            ->setInternalDate(new \DateTime())
            ->setMessageId('test-message-id');
        $this->persistTestEntity($email);

        $emailUser = (new EmailUser())
            ->setEmail($email)
            ->setOwner($user)
            ->setOrganization($organization)
            ->setReceivedAt(new \DateTime());
        $this->persistTestEntity($emailUser);

        $item = new Item();
        $item->stringValue = 'Test Item';
        $item->integerValue = 42;
        $item->decimalValue = 3.14;
        $item->floatValue = 2.71;
        $item->datetimeValue = new \DateTime();
        $item->blobValue = 'Test blob value';
        $item->phone = '555-0300';
        $item->owner = $user;
        $item->organization = $organization;
        $this->persistTestEntity($item);

        $item2 = new Item2();
        $this->persistTestEntity($item2);

        $product = new Product();
        $product->setName('Test Product');
        $this->persistTestEntity($product);

        $manager->flush();
    }
}
