<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class MenuUpdateTypeTest extends WebTestCase
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $formOptions
     * @param array $submitData
     * @param bool $expectedFormIsValid
     * @param bool $doAssertEntity
     */
    public function testSubmit($formOptions, $submitData, $expectedFormIsValid, $doAssertEntity)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository('OroLocaleBundle:Localization');

        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();

        $submitData = array_merge_recursive(
            ['_token' => $this->tokenManager->getToken(MenuUpdateType::NAME)->getValue()],
            $submitData
        );

        foreach ($localizations as $localization) {
            $localizationId = $localization->getId();
            $submitData['titles']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
        }

        $form = $this->formFactory->create(MenuUpdateType::NAME, new MenuUpdate(), $formOptions);
        $form->submit($submitData);
        $this->assertEquals($expectedFormIsValid, $form->isValid());

        if ($doAssertEntity) {
            /** @var MenuUpdate $menuUpdate */
            $menuUpdate = $form->getData();
            $this->assertInstanceOf('Oro\Bundle\NavigationBundle\Entity\MenuUpdate', $menuUpdate);
            $this->assertEquals($submitData['titles']['values']['default'], (string)$menuUpdate->getDefaultTitle());
            $this->assertEquals($submitData['uri'], $menuUpdate->getUri());

            foreach ($localizations as $localization) {
                $this->assertLocalization($localization, $menuUpdate);
            }
        }
    }

    public function submitProvider()
    {
        return [
            'update existing menu update' => [
                'formOptions' => ['validation_groups' => ['Default']],
                'submitData' => [
                    'titles' => ['values' => ['default' => 'Item Title']],
                    'uri'    => '/some/path',
                ],
                'expectedFormIsValid' => true,
                'doAssertEntity' => true,
            ],
            'update existing menu update without uri' => [
                'formOptions' => ['validation_groups' => ['Default']],
                'submitData' => [
                    'titles' => ['values' => ['default' => 'Item Title']],
                    'uri'    => null,
                ],
                'expectedFormIsValid' => true,
                'doAssertEntity' => true,
            ],
            'create new menu update' => [
                'formOptions' => ['validation_groups' => ['Default', 'Create']],
                'submitData' => [
                    'titles' => ['values' => ['default' => 'Item Title']],
                    'uri'    => '/some/path',
                ],
                'expectedFormIsValid' => true,
                'doAssertEntity' => true,
            ],
            'create new menu update without title should fail' => [
                'formOptions' => ['validation_groups' => ['Default', 'Create']],
                'submitData' => [
                    'uri'    => '/some/path',
                ],
                'expectedFormIsValid' => false,
                'doAssertEntity' => false,
            ],
        ];
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization $localization
     * @return LocalizedFallbackValue|null
     */
    protected function getValueByLocalization($values, Localization $localization)
    {
        $localizationId = $localization->getId();
        foreach ($values as $value) {
            if ($value->getLocalization()->getId() == $localizationId) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param Localization $localization
     * @param MenuUpdate $menuUpdate
     */
    protected function assertLocalization($localization, $menuUpdate)
    {
        $localizedTitle = $this->getValueByLocalization($menuUpdate->getTitles(), $localization);
        $this->assertNotEmpty($localizedTitle);
        $this->assertEmpty($localizedTitle->getString());
        $this->assertEquals(FallbackType::SYSTEM, $localizedTitle->getFallback());
    }
}
