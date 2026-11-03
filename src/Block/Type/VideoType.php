<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A video: a title and a YouTube embed code, URL or id.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'video',
    preview: '@GingermindsCms/block/video/preview.html.twig',
    icon: 'bi-play-circle',
    order: 200,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('embed_code', TextType::class, ['constraints' => [new NotBlank()], 'help' => 'block.video.help.embed_code']);
    }

    /**
     * The YouTube id of an embed code: the id itself, or found in a YouTube URL.
     */
    public static function youtubeId(?string $embedCode): ?string
    {
        $embedCode = trim((string) $embedCode);

        if (1 === preg_match('#(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:embed/|v/|shorts/|live/|watch\?(?:.*&)?v=))([\w-]{11})#', $embedCode, $matches)) {
            return $matches[1];
        }

        return 1 === preg_match('/^[\w-]{11}$/', $embedCode) ? $embedCode : null;
    }
}
