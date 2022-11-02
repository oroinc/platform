<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Form\ChoiceList\AclProtectedQueryBuilderLoader;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds ACL protection to the query builder and "acl_options" option to EntityType form type.
 *
 * The "acl_options" can have the following attributes:
 * * "disable" - disable ACL check if the value of attribute is true.
 * * "permission" - the permission name the ACL checks should be applied. By default is 'VIEW'.
 * * "options" - an array with additional ACL rules options.
 */
class AclProtectedTypeExtension extends AbstractTypeExtension
{
    /** @var AclHelper */
    private $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $aclHelper = $this->aclHelper;
        $loader = function (Options $options) use ($aclHelper) {
            if (null !== $options['choices']) {
                return null;
            }

            /** @var EntityManagerInterface $em */
            $em = $options['em'];
            // create simple QB in order to prevent loading all entities from repo by EntityChoiceList
            /** @var QueryBuilder $qb */
            $qb = $options['query_builder'] ?? $em->getRepository($options['class'])->createQueryBuilder('e');

            $aclOptions = $options['acl_options'] ?? [];
            if (!isset($aclOptions['disable']) || true !== $aclOptions['disable']) {
                $entityLoader = new AclProtectedQueryBuilderLoader(
                    $aclHelper,
                    $qb,
                    $em,
                    $options['class'],
                    $aclOptions['permission'] ?? 'VIEW',
                    $aclOptions['options'] ?? []
                );
            } else {
                $entityLoader = new ORMQueryBuilderLoader(
                    $qb
                );
            }

            return new DoctrineChoiceLoader(
                $em,
                $options['class'],
                $options['id_reader'],
                $entityLoader
            );
        };
        $resolver->setDefaults(['choice_loader' => $loader]);
        $resolver->setDefined('acl_options');
    }
}
