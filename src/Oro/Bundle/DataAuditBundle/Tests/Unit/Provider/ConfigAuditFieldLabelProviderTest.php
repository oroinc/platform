<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditFieldLabelProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\TranslatorStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigAuditFieldLabelProviderTest extends TestCase
{
    private const string SYSTEM = 'Oro\Bundle\ConfigBundle\SystemConfiguration';
    private const string MAX_ITEMS = 'oro_product.new_arrivals_max_items';
    private const string PER_PAGE = 'oro_ui.items_per_page';

    private ConfigBag&MockObject $configBag;
    private TranslatorStub $translator;
    private ConfigAuditFieldLabelProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->translator = new TranslatorStub();

        $this->provider = new ConfigAuditFieldLabelProvider(
            $this->configBag,
            new ConfigAuditLevelProvider([
                'customer' => 'Oro\\Bundle\\CustomerBundle\\Entity\\Customer',
                'customer_group' => 'Oro\\Bundle\\CustomerBundle\\Entity\\CustomerGroup',
                'website' => 'Oro\\Bundle\\WebsiteBundle\\Entity\\Website',
                'user' => 'Oro\\Bundle\\UserBundle\\Entity\\User',
                'organization' => 'Oro\\Bundle\\OrganizationBundle\\Entity\\Organization',
                'global' => null,
            ]),
            $this->translator
        );
    }

    public function testReturnsNullForNonConfigType(): void
    {
        self::assertNull($this->provider->getLabel('Oro\Bundle\UserBundle\Entity\User', 'username'));
        self::assertNull($this->provider->getLabel(null, 'oro_test.foo'));
    }

    public function testBuildsBreadcrumbEndingWithLabelAndDropsGenericRoot(): void
    {
        $this->givenSystemConfigurationTree();

        // "commerce" root kept, "platform" (System Configuration) root dropped, label comes last.
        self::assertSame(
            'Commerce › Product › Promotions › Maximum Items',
            $this->provider->getLabel(self::SYSTEM, self::MAX_ITEMS)
        );
        self::assertSame(
            'General Setup › Display Settings › Records Per Page',
            $this->provider->getLabel(self::SYSTEM, self::PER_PAGE)
        );
    }

    public function testDeduplicatesLabelWhenInnermostGroupHasTheSameTitle(): void
    {
        $this->givenTree(
            ['commerce' => ['children' => ['contact_info' => ['children' => ['oro_test.leaf']]]]],
            ['commerce' => 'title.commerce', 'contact_info' => 'title.leaf'],
            ['oro_test.leaf' => 'label.leaf']
        );
        $this->translator->catalogue['en'] = [
            'title.commerce' => 'Commerce',
            'title.leaf' => 'Contact Info',
            'label.leaf' => 'Contact Info',
        ];

        self::assertSame('Commerce › Contact Info', $this->provider->getLabel(self::SYSTEM, 'oro_test.leaf'));
    }

    public function testFallsBackToLabelOrKeyWhenNotInTree(): void
    {
        $this->givenTree([], [], ['oro_test.orphan' => 'label.orphan']);
        $this->translator->catalogue = ['en' => ['label.orphan' => 'Orphan Setting']];

        // A setting missing from the tree still gets its own label, and an unknown key stays as it is.
        self::assertSame('Orphan Setting', $this->provider->getLabel(self::SYSTEM, 'oro_test.orphan'));
        self::assertSame('oro_test.unknown', $this->provider->getLabel(self::SYSTEM, 'oro_test.unknown'));
    }

    /**
     * @dataProvider matchingTermDataProvider
     */
    public function testGetMatchingFieldKeysMatchesAnyPartOfTheBreadcrumb(string $term, array $expected): void
    {
        $this->givenSystemConfigurationTree();

        self::assertEqualsCanonicalizing($expected, $this->provider->getMatchingFieldKeys($term));
    }

    public function matchingTermDataProvider(): array
    {
        return [
            'setting label' => ['Maximum Items', [self::MAX_ITEMS]],
            'part of the setting label, case insensitive' => ['maXimum', [self::MAX_ITEMS]],
            'innermost group title' => ['Promotions', [self::MAX_ITEMS]],
            'intermediate group title' => ['Product', [self::MAX_ITEMS]],
            'root group title matches everything under it' => ['Commerce', [self::MAX_ITEMS]],
            'another branch' => ['Display Settings', [self::PER_PAGE]],
            'generic root is not matchable, as it is not displayed' => ['System Configuration', []],
            'no match' => ['nothing here', []],
            'empty term' => ['   ', []],
        ];
    }

    public function testResolvesLabelsAndSearchInTheCurrentLocale(): void
    {
        $this->givenSystemConfigurationTree();
        $this->translator->catalogue['fr'] = [
            'title.commerce' => 'Commerce',
            'title.product' => 'Produit',
            'title.promotions' => 'Offres',
            'label.max_items' => 'Nombre maximum',
        ];

        // Searching in the language the viewer sees works; the term of another language does not leak in.
        self::assertSame([self::MAX_ITEMS], $this->provider->getMatchingFieldKeys('Promotions'));
        self::assertSame([], $this->provider->getMatchingFieldKeys('Offres'));

        $this->translator->setLocale('fr');

        self::assertSame(
            'Commerce › Produit › Offres › Nombre maximum',
            $this->provider->getLabel(self::SYSTEM, self::MAX_ITEMS)
        );
        self::assertSame([self::MAX_ITEMS], $this->provider->getMatchingFieldKeys('offres'));
        self::assertSame([self::MAX_ITEMS], $this->provider->getMatchingFieldKeys('nombre'));
        self::assertSame([], $this->provider->getMatchingFieldKeys('Promotions'));
    }

    private function givenSystemConfigurationTree(): void
    {
        $this->givenTree(
            [
                'commerce' => ['children' => [
                    'product' => ['children' => [
                        'promotions' => ['children' => [self::MAX_ITEMS]],
                    ]],
                ]],
                'platform' => ['children' => [
                    'general_setup' => ['children' => [
                        'display' => ['children' => [self::PER_PAGE]],
                    ]],
                ]],
            ],
            [
                'commerce' => 'title.commerce',
                'product' => 'title.product',
                'promotions' => 'title.promotions',
                'platform' => 'title.platform',
                'general_setup' => 'title.general_setup',
                'display' => 'title.display',
            ],
            [
                self::MAX_ITEMS => 'label.max_items',
                self::PER_PAGE => 'label.per_page',
            ]
        );
        $this->translator->catalogue['en'] = [
            'title.commerce' => 'Commerce',
            'title.product' => 'Product',
            'title.promotions' => 'Promotions',
            'title.platform' => 'System Configuration',
            'title.general_setup' => 'General Setup',
            'title.display' => 'Display Settings',
            'label.max_items' => 'Maximum Items',
            'label.per_page' => 'Records Per Page',
        ];
    }

    /**
     * @param array $tree the system_configuration tree (the other levels have no tree)
     * @param array<string, string> $groupTitles [group name => title translation key]
     * @param array<string, string> $fieldLabels [config key => label translation key]
     */
    private function givenTree(array $tree, array $groupTitles, array $fieldLabels): void
    {
        $this->configBag->expects(self::any())
            ->method('getTreeRoot')
            ->willReturnCallback(static fn (string $name): array|bool => 'system_configuration' === $name
                ? $tree
                : false);
        $this->configBag->expects(self::any())
            ->method('getGroupsNode')
            ->willReturnCallback(static fn (string $name): array|bool => isset($groupTitles[$name])
                ? ['title' => $groupTitles[$name]]
                : false);
        $this->configBag->expects(self::any())
            ->method('getFieldsRoot')
            ->willReturnCallback(static fn (string $name): array|bool => isset($fieldLabels[$name])
                ? ['options' => ['label' => $fieldLabels[$name]]]
                : false);
    }
}
