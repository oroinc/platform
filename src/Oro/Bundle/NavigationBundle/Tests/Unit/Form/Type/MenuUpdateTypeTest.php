<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub\OroIconTypeStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\EventSubscriber\LocalizedFallbackValueCollectionClearingSubscriber;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevelValidator;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\SecurityBundle\Validator\Constraints\NotDangerousProtocolValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\HttpKernel\KernelInterface;

class MenuUpdateTypeTest extends FormIntegrationTestCase
{
    use MenuItemTestTrait;

    private const TEST_TITLE = 'Test Title';
    private const TEST_DESCRIPTION = 'Test Description';
    private const TEST_URI = 'http://test_uri';
    private const TEST_ACL_RESOURCE_ID = 'test_acl_resource_id';

    protected function getExtensions(): array
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $kernel = $this->createMock(KernelInterface::class);
        $menuUpdateType = new MenuUpdateType();
        $menuUpdateType->setLocalizedFallbackValueCollectionSubscriber(
            new LocalizedFallbackValueCollectionClearingSubscriber()
        );

        return [
            new PreloadedExtension(
                [
                    new LocalizedFallbackValueCollectionType($registry),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    OroIconType::class => new OroIconTypeStub($kernel),
                    MenuUpdateType::class => $menuUpdateType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators(): array
    {
        $uriSecurityHelper = $this->createMock(UriSecurityHelper::class);
        $uriSecurityHelper->expects(self::any())
            ->method('uriHasDangerousProtocol')
            ->willReturnMap([
                ['javascript:alert(1)', true],
                ['jAvAsCrIpt:alert(1)', true],
                ['data:base64,samplebase64', true],
                ['dAtA:base64,samplebase64', true],
                [self::TEST_URI, false],
            ]);

        return [
            MaxNestedLevelValidator::class => $this->createMock(MaxNestedLevelValidator::class),
            'oro_security.validator.constraints.not_dangerous_protocol' =>
                new NotDangerousProtocolValidator($uriSecurityHelper),
        ];
    }

    public function testSubmitValid(): void
    {
        $menuUpdate = new MenuUpdate();
        $menu = $this->createItem('sample_menu');
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE,
                    ],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => self::TEST_DESCRIPTION,
                    ],
                ],
                'icon' => 'fa-anchor',
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected->addTitle($expectedTitle);
        $expected->setIcon('fa-anchor');

        $expectedDescription = (new LocalizedFallbackValue)->setText(self::TEST_DESCRIPTION);
        $expected->addDescription($expectedDescription);

        $this->assertFormOptionEqual(true, 'disabled', $form->get('uri'));
        $this->assertFormNotContainsField('aclResourceId', $form);

        $this->assertFormIsValid($form);
        self::assertEquals($expected, $form->getData());
    }

    public function testSubmitIsCustom(): void
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);
        $menu = $this->createItem('sample_menu');

        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE,
                    ],
                ],
                'uri' => self::TEST_URI,
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addTitle($expectedTitle)
            ->addDescription(new LocalizedFallbackValue)
            ->setUri(self::TEST_URI);

        $this->assertFormIsValid($form);
        self::assertEquals($expected, $form->getData());
    }

    public function testSubmitEmptyTitle(): void
    {
        $menuUpdate = new MenuUpdate();
        $menu = $this->createItem('sample_menu');
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit([]);

        $expected = new MenuUpdate();
        $expectedTitle = new LocalizedFallbackValue;
        $expected->addTitle($expectedTitle);
        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormIsNotValid($form);
        self::assertEquals($expected, $form->getData());
    }

    public function testSubmitWhenTitleIsNotChanged(): void
    {
        $menuUpdate = (new MenuUpdate())
            ->addTitle((new LocalizedFallbackValue())->setString(self::TEST_TITLE));
        $menu = $this->createItem('sample_menu');
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit([
            'titles' => [
                'values' => [
                    'default' => self::TEST_TITLE,
                ],
            ]
        ]);

        $this->assertFormIsValid($form);
        self::assertEquals([], $form->getData()->getTitles()->toArray());
    }

    public function testSubmitWhenTitleIsChanged(): void
    {
        $menuUpdate = (new MenuUpdate())
            ->addTitle((new LocalizedFallbackValue())->setString(self::TEST_TITLE));
        $menu = $this->createItem('sample_menu');
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit([
            'titles' => [
                'values' => [
                    'default' => self::TEST_TITLE . ' updated',
                ],
            ]
        ]);

        $this->assertFormIsValid($form);
        self::assertEqualsCanonicalizing(
            [(new LocalizedFallbackValue())->setString(self::TEST_TITLE . ' updated')],
            $form->getData()->getTitles()->toArray()
        );
    }

    public function testSubmitCustomWithEmptyUri(): void
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);
        $menu = $this->createItem('sample_menu');
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE,
                    ],
                ],
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addDescription(new LocalizedFallbackValue)
            ->addTitle($expectedTitle);

        $this->assertFormIsNotValid($form);
        self::assertEquals($expected, $form->getData());
    }

    public function testAclResourceIdShouldExist(): void
    {
        $menuUpdate = new MenuUpdate();
        $menu = $this->createItem('sample_menu');
        $menuItem = $this->createItem('item_1')
            ->setExtra('acl_resource_id', self::TEST_ACL_RESOURCE_ID);

        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu_item' => $menuItem, 'menu' => $menu]);

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected->addTitle($expectedTitle);
        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormContainsField('aclResourceId', $form);
        $this->assertFormOptionEqual(true, 'disabled', $form->get('aclResourceId'));
    }

    /**
     * @dataProvider submitCustomWithJavascriptUriDataProvider
     */
    public function testSubmitCustomWithJavascriptUri(string $uri): void
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);
        $menu = $this->createItem('sample_menu');
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu' => $menu]);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE,
                    ],
                ],
                'uri' => $uri,
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addDescription(new LocalizedFallbackValue)
            ->addTitle($expectedTitle)
            ->setUri($uri);

        $this->assertFormIsNotValid($form);
        self::assertEquals($expected, $form->getData());
    }

    public function submitCustomWithJavascriptUriDataProvider(): array
    {
        return [
            ['uri' => 'javascript:alert(1)'],
            ['uri' => 'jAvAsCrIpt:alert(1)'],
            ['uri' => 'data:base64,samplebase64'],
            ['uri' => 'dAtA:base64,samplebase64'],
            // We don't have to check other variants like with html-, ascii-, utf- encoded characters etc because
            // all of them must be handled on the output by twig.
        ];
    }
}
