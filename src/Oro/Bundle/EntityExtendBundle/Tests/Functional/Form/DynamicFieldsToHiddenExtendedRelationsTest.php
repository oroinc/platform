<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Form;

use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntityRelatesToHidden;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

class DynamicFieldsToHiddenExtendedRelationsTest extends WebTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->initClient();
        $this->formFactory = self::getContainer()->get('form.factory');
    }

    public function testThatRelationFeldsToHiddenEntitiesNotDynamiclyAdded(): void
    {
        $form = $this->formFactory
            ->createBuilder(FormType::class, new TestExtendedEntityRelatesToHidden())
            ->getForm();

        self::assertFalse($form->has('tee_to_hidden_otm'));
        self::assertFalse($form->has('tee_to_hidden_mtm'));
        self::assertFalse($form->has('default_tee_to_hidden_otm'));
        self::assertFalse($form->has('default_tee_to_hidden_mtm'));
    }
}
