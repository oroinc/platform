<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\AutoResponseRuleCondition;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides methods to manage auto-response rules.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoResponseManager
{
    const INDEX_PLACEHOLDER = '__index__';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EmailModelBuilder */
    protected $emailBuilder;

    protected EmailModelSender $emailModelSender;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ConfigExpressions */
    protected $configExpressions;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /** @var string */
    protected $defaultLocale;

    /** @var EmailRenderer */
    protected $emailRender;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $filterToConditionMap = [
        TextFilterType::TYPE_CONTAINS => 'contains',
        TextFilterType::TYPE_ENDS_WITH => 'end_with',
        TextFilterType::TYPE_EQUAL => 'eq',
        TextFilterType::TYPE_IN => 'in',
        TextFilterType::TYPE_NOT_CONTAINS => 'not_contains',
        TextFilterType::TYPE_NOT_IN => 'not_in',
        TextFilterType::TYPE_STARTS_WITH => 'start_with',
        FilterUtility::TYPE_EMPTY => 'empty',
        FilterUtility::TYPE_NOT_EMPTY => 'not_empty',
    ];

    /** @var array */
    protected $filterOperatorToExprMap = [
        FilterUtility::CONDITION_AND => '@and',
        FilterUtility::CONDITION_OR => '@or',
    ];

    public function __construct(
        ManagerRegistry $registry,
        EmailModelBuilder $emailBuilder,
        EmailModelSender $emailModelSender,
        EmailRenderer $emailRender,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        string $defaultLocale
    ) {
        $this->registry = $registry;
        $this->emailBuilder = $emailBuilder;
        $this->emailModelSender = $emailModelSender;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->defaultLocale = $defaultLocale;
        $this->configExpressions = new ConfigExpressions();
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->emailRender = $emailRender;
    }

    /**
     * @param Email $email
     *
     * @return bool
     */
    public function hasAutoResponses(Email $email)
    {
        $mailboxes = $this->getMailboxRepository()->findForEmail($email);
        foreach ($mailboxes as $mailbox) {
            if ($this->getApplicableRules($mailbox, $email)->count()) {
                return true;
            }
        }

        return false;
    }

    public function sendAutoResponses(Email $email)
    {
        $mailboxes = $this->getMailboxRepository()->findForEmail($email);
        foreach ($mailboxes as $mailbox) {
            $this->processMailbox($mailbox, $email);
        }
    }

    /**
     * @return array
     */
    public function createEmailEntity()
    {
        return [
            'name' => 'email',
            'label' => $this->translator->trans('oro.email.entity_label'),
            'fields' => [
                $this->createField('subject', 'oro.email.subject.label'),
                $this->createField('emailBody.bodyContent', 'oro.email.email_body.label'),
                $this->createField('fromName', 'From'),
                $this->createField(sprintf('cc.%s.name', static::INDEX_PLACEHOLDER), 'Cc'),
                $this->createField(sprintf('bcc.%s.name', static::INDEX_PLACEHOLDER), 'Bcc'),
            ],
        ];
    }

    protected function createField(string $name, string $label): array
    {
        return [
            'label' => $this->translator->trans($label),
            'name' => $name,
            'type' => 'text',
        ];
    }

    protected function processMailbox(Mailbox $mailbox, Email $email)
    {
        $rules = $this->getApplicableRules($mailbox, $email);
        $emailModels = $this->createReplyEmailModels($email, $rules);
        foreach ($emailModels as $emailModel) {
            $this->sendEmailModel($emailModel, $mailbox->getOrigin());
        }
    }

    protected function sendEmailModel(EmailModel $emailModel, EmailOrigin $origin = null)
    {
        try {
            $this->emailModelSender->send($emailModel, $origin);
        } catch (\RuntimeException $exception) {
            $this->logger->error(
                sprintf(
                    'Failed to send email model to %s: %s',
                    implode(', ', $emailModel->getTo()),
                    $exception->getMessage()
                ),
                ['exception' => $exception, 'emailModel' => $emailModel]
            );
        }
    }

    /**
     * @param Email $email
     * @param AutoResponseRule[]|Collection $rules
     *
     * @return EmailModel[]|Collection
     */
    protected function createReplyEmailModels(Email $email, Collection $rules)
    {
        return $rules->map(function (AutoResponseRule $rule) use ($email) {
            $emailModel = $this->emailBuilder->createReplyEmailModel($email);
            $emailModel->setFrom($rule->getMailbox()->getEmail());
            $emailModel->setTo([$email->getFromEmailAddress()->getEmail()]);
            $emailModel->setContexts(array_merge([$email], $emailModel->getContexts()->toArray()));

            $this->applyTemplate($emailModel, $rule->getTemplate(), $email);

            return $emailModel;
        });
    }

    protected function applyTemplate(EmailModel $emailModel, EmailTemplate $template, Email $email)
    {
        $translatedSubjects = [];
        $translatedContents = [];
        /** @var EmailTemplateTranslation $translation */
        foreach ($template->getTranslations() as $translation) {
            $langCode = $translation->getLocalization()->getLanguageCode();
            if ($translation->getSubject()) {
                $translatedSubjects[$langCode] = $translation->getSubject();
            }
            if ($translation->getContent()) {
                $translatedContents[$langCode] = $translation->getContent();
            }
        }

        $locales = array_flip(array_merge($email->getAcceptedLocales(), [$this->defaultLocale]));
        $comparator = ArrayUtil::createOrderedComparator($locales);
        uksort($translatedSubjects, $comparator);
        uksort($translatedContents, $comparator);

        $subjects = array_intersect_key($translatedSubjects, $locales);
        $subject = reset($subjects);
        if (false === $subject) {
            $subject = $template->getSubject() ?? '';
        }

        $contents = array_intersect_key($translatedContents, $locales);
        $content = reset($contents);
        if (false === $content) {
            $content = $template->getContent() ?? '';
        }

        $emailModel
            ->setSubject($this->emailRender->renderTemplate($subject, ['entity' => $email]))
            ->setBody($this->emailRender->renderTemplate($content, ['entity' => $email]))
            ->setType($template->getType());
    }

    /**
     * @param MailBox $mailbox
     * @param Email $email
     *
     * @return AutoResponseRule[]|Collection
     */
    public function getApplicableRules(Mailbox $mailbox, Email $email)
    {
        return $mailbox->getAutoResponseRules()->filter(function (AutoResponseRule $rule) use ($email) {
            return $rule->isActive()
                && $this->isExprApplicable($email, $this->createRuleExpr($rule, $email))
                && $rule->getCreatedAt() < $email->getSentAt();
        });
    }

    /**
     * @param Email $email
     * @param array $expr
     *
     * @return bool
     */
    protected function isExprApplicable(Email $email, array $expr)
    {
        return (bool)$this->configExpressions->evaluate($expr, $email);
    }

    /**
     * @param AutoResponseRule $rule
     * @param Email $email
     *
     * @return array
     */
    public function createRuleExpr(AutoResponseRule $rule, Email $email)
    {
        $definition = json_decode($rule->getDefinition(), true);
        if (!$definition || !isset($definition['filters'])) {
            return [];
        }

        return $this->createGroupExpr([$definition['filters']], $email);
    }

    protected function createGroupExpr(array $group, Email $email)
    {
        $exprs = [];
        $operators = [];

        foreach ($group as $filter) {
            if (is_string($filter)) {
                $operators[] = $filter;
            } elseif (is_array($filter)) {
                if (array_key_exists('columnName', $filter)) {
                    $condition = AutoResponseRuleCondition::createFromFilter($filter);
                    $exprs[] = $this->createFilterExpr($condition, $email);
                } else {
                    $exprs[] = $this->createGroupExpr($filter, $email);
                }
            }
        }

        if (!$exprs) {
            return [];
        }

        if (count($exprs) - count($operators) !== 1) {
            throw new \LogicException('Number of operators have to be about 1 less than number of exprs.');
        }

        $exprOperators = array_map(function ($operator) {
            return $this->filterOperatorToExprMap[$operator];
        }, $operators);

        return array_reduce($exprs, function ($carry, $expr) use (&$exprOperators) {
            if (!$carry) {
                return $expr;
            }

            return [array_shift($exprOperators) => [$carry, $expr]];
        });
    }

    /**
     * @param AutoResponseRuleCondition $condition
     * @param Email $email
     *
     * @return array
     */
    protected function createFilterExpr(AutoResponseRuleCondition $condition, Email $email)
    {
        $paths = $this->getFieldPaths($condition->getField(), $email);

        $args = [null];
        if (!in_array($condition->getFilterType(), [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            $args[] = $this->parseValue($condition->getFilterValue(), $condition->getFilterType());
        }

        $configs = [];
        foreach ($paths as $path) {
            $args[0] = $path;
            $configKey = sprintf('@%s', $this->filterToConditionMap[$condition->getFilterType()]);
            $configs[] = [
                $configKey => $args,
            ];
        }

        return $configs ? [$this->filterOperatorToExprMap[FilterUtility::CONDITION_OR] => $configs] : [];
    }

    /**
     * @param string $field
     * @param object $context
     *
     * @return string[]
     */
    protected function getFieldPaths($field, $context)
    {
        $delimiter = sprintf('.%s.', static::INDEX_PLACEHOLDER);
        if (str_contains($field, $delimiter)) {
            [$leftPart] = explode($delimiter, $field);
            $collection = $this->accessor->getValue($context, $leftPart);
            if (!$collection) {
                return [];
            }

            $keys = $collection->getKeys();
            if (!$keys) {
                return [];
            }

            return array_map(function ($key) use ($field) {
                return sprintf('$%s', str_replace(static::INDEX_PLACEHOLDER, $key, $field));
            }, $keys);
        }

        return [sprintf('$%s', $field)];
    }

    /**
     * @param string $value
     * @param string $type
     *
     * @return mixed
     */
    protected function parseValue($value, $type)
    {
        $arrayTypes = [TextFilterType::TYPE_IN, TextFilterType::TYPE_NOT_IN];

        if (in_array($type, $arrayTypes)) {
            return array_map('trim', explode(',', $value));
        }

        if ($value === null) {
            return '';
        }

        return $value;
    }

    /**
     * @return MailboxRepository
     */
    protected function getMailboxRepository()
    {
        return $this->registry->getRepository(Mailbox::class);
    }
}
