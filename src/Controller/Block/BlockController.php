<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Block;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\ContentBlock;
use Gingerminds\CmsBundle\Block\Reference\ContentReferenceResolver;
use Gingerminds\CoreBundle\Security\Voter\ResourceVoter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The block modal of a canvas (BlockCanvasType), for the editors of a resource (route default
 * `resource`, e.g. `page`: its create or edit permission):
 * - `form` (POST `uid`, `data` JSON of the edited block): the block form;
 * - `validate` (POST the block form): the normalized data and the preview, or the form with its
 *   errors (422).
 */
class BlockController
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly BlockContentNormalizer $normalizer,
        private readonly ContentReferenceResolver $references,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
    ) {
    }

    public function form(Request $request, string $resource, string $key): JsonResponse
    {
        $block = $this->block($resource, $key);
        $data = json_decode($request->request->getString('data'), true);
        $form = $this->registry->createForm($block, $this->normalizer->normalizeData($block, \is_array($data) ? $data : []));

        return $this->formResponse($block, $form, $this->uid($request));
    }

    public function validate(Request $request, string $resource, string $key): JsonResponse
    {
        $block = $this->block($resource, $key);
        $uid = $this->uid($request);
        $form = $this->registry->createForm($block)->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->formResponse($block, $form, $uid, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var array<string, mixed> $data */
        $data = $form->getData() ?? [];

        return new JsonResponse([
            ...$this->payload($block, $uid),
            'data' => $data,
            'preview' => $this->twig->render($block->previewTemplate, [
                'block' => $block,
                'data' => $data,
                'refs' => $this->references->entities([['type' => $block->key, 'data' => $data]])[0]['data'] ?? $data,
                'uid' => $uid,
            ]),
        ]);
    }

    private function block(string $resource, string $key): ContentBlock
    {
        if (!$this->authorizationChecker->isGranted(ResourceVoter::CREATE, $resource) && !$this->authorizationChecker->isGranted(ResourceVoter::EDIT, $resource)) {
            throw new AccessDeniedException();
        }

        return $this->registry->find($key) ?? throw new NotFoundHttpException(\sprintf('Unknown content block "%s".', $key));
    }

    /**
     * @param FormInterface<array<string, mixed>|null> $form
     */
    private function formResponse(ContentBlock $block, FormInterface $form, string $uid, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            ...$this->payload($block, $uid),
            'html' => $this->twig->render('@GingermindsCms/block/_form.html.twig', ['form' => $form->createView(), 'block' => $block, 'uid' => $uid]),
        ], $status);
    }

    /**
     * @return array{uid: string, type: string, label: string, icon: string}
     */
    private function payload(ContentBlock $block, string $uid): array
    {
        return [
            'uid' => $uid,
            'type' => $block->key,
            'label' => $this->translator->trans($block->label, [], $block->translationDomain),
            'icon' => $block->icon,
        ];
    }

    private function uid(Request $request): string
    {
        $uid = $request->request->getString('uid');

        return 1 === preg_match('/^[\w-]{1,64}$/', $uid) ? $uid : bin2hex(random_bytes(16));
    }
}
