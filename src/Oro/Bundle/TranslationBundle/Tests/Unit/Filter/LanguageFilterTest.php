<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Filter\LanguageFilter;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;
use Symfony\Component\Form\FormFactoryInterface;

class LanguageFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LanguageFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new LanguageFilter($this->formFactory, new FilterUtility());
        $this->filter->setDoctrine($this->doctrine);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formFactory, $this->filter);
    }

    public function testInit()
    {
        $this->filter->init('test', []);
        $this->assertAttributeEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => Language::class,
                        'choice_label' => 'code',
                    ],
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice',
            ],
            'params',
            $this->filter
        );
    }

    public function testGetForm()
    {
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(LanguageFilterType::class);

        $this->filter->getForm();
    }
}
