<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Type;

use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Model\PublishableInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The status of a publishable entity (`subject`): its current place and the places reachable by an
 * enabled transition of its workflow (`workflow`, default `gingerminds_cms_publication`), applied
 * on submit. Not mapped: the workflow sets the status.
 *
 *     $builder->add('status', PublicationStatusType::class, ['subject' => $event]);
 *
 * @extends AbstractType<string>
 */
final class PublicationStatusType extends AbstractType
{
    public function __construct(
        private readonly Registry $workflows,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PublishableInterface $subject */
        $subject = $options['subject'];
        $workflow = $this->workflow($subject, $options['workflow']);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($subject, $workflow): void {
            $status = $event->getForm()->getData();

            if (!\is_string($status) || $status === $subject->getStatus()) {
                return;
            }

            // The choices are the reachable places only: another value is an invalid choice.
            foreach ($workflow->getEnabledTransitions($subject) as $transition) {
                if (\in_array($status, $transition->getTos(), true)) {
                    $workflow->apply($subject, $transition->getName());

                    return;
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('subject')
            ->setAllowedTypes('subject', PublishableInterface::class)
            ->setDefaults([
                'workflow' => PublicationStatus::WORKFLOW,
                'mapped' => false,
                'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
                'choice_translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
            ])
            ->setAllowedTypes('workflow', 'string')
            ->setNormalizer('choices', fn (Options $options): array => array_flip(PublicationStatus::labels($this->reachableStatuses($options['subject'], $options['workflow']))))
            ->setNormalizer('data', static fn (Options $options): string => $options['subject']->getStatus());
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return list<string>
     */
    private function reachableStatuses(PublishableInterface $subject, string $workflow): array
    {
        $statuses = [$subject->getStatus()];

        foreach ($this->workflow($subject, $workflow)->getEnabledTransitions($subject) as $transition) {
            array_push($statuses, ...$transition->getTos());
        }

        return array_values(array_unique($statuses));
    }

    private function workflow(PublishableInterface $subject, string $name): WorkflowInterface
    {
        return $this->workflows->get($subject, $name);
    }
}
