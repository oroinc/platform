<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\CalendarBundle\Form\Type\ContextsToViewTransformer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;

class CalendarEventAttendeesSelectType extends AbstractType
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ObjectMapper */
    protected $mapper;

    /* @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EntityManager         $entityManager
     * @param ConfigManager         $configManager
     * @param TranslatorInterface   $translator
     * @param ObjectMapper          $mapper
     * @param TokenStorageInterface $securityTokenStorage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        ObjectMapper $mapper,
        TokenStorageInterface $securityTokenStorage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager        = $entityManager;
        $this->configManager        = $configManager;
        $this->translator           = $translator;
        $this->mapper               = $mapper;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->dispatcher           = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new ContextsToViewTransformer(
                $this->entityManager,
                $this->configManager,
                $this->translator,
                $this->mapper,
                $this->securityTokenStorage,
                $this->dispatcher
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-selected-data'] = $view->vars['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'tooltip' => false,
            'configs' => [
                'placeholder'        => 'oro.user.form.choose_user',
                'allowClear'         => true,
                'multiple'           => true,
                'separator'          => ';',
                'forceSelectedData'  => true,
                'minimumInputLength' => 0,
                'route_name'         => 'oro_calendarevent_autocomplete_attendees',
                'route_parameters'   => [
                    'name' => 'name',
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event_attendees_select';
    }
}
