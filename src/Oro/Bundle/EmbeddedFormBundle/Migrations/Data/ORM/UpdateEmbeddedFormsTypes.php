<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * This fixtures aims to update forms' types for oro_contact_us.embedded_form and oro_magento_contact_us.embedded_form
 * embedded forms.
 */
class UpdateEmbeddedFormsTypes extends AbstractFixture implements
    ContainerAwareInterface,
    RenamedFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\EmbeddedForm\\Migrations\\Data\\ORM\\UpdateEmbeddedFormsTypes',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateFormType(
            'oro_contact_us.embedded_form',
            'Oro\Bundle\ContactUsBundle\Form\Type\ContactRequestType'
        );
        $this->updateFormType(
            'oro_magento_contact_us.embedded_form',
            'Oro\Bundle\MagentoContactUsBundle\Form\Type\ContactRequestType'
        );
    }

    private function updateFormType(string $formAlias, string $formType): int
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->container->get('doctrine')->getConnection()->createQueryBuilder();

        $queryBuilder
            ->update('oro_embedded_form', 'form')
            ->set('form_type', ':type')
            ->where($queryBuilder->expr()->eq('form_type', ':alias'))
            ->setParameter('type', $formType)
            ->setParameter('alias', $formAlias);

        return $queryBuilder->execute();
    }
}
