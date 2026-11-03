<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Block;

use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Entities as strings, the loaded ids recorded (ContentReferenceResolverTest).
 */
final class FakeReferenceResolver implements BlockReferenceResolverInterface
{
    /** @var list<list<int|string>> */
    public array $loads = [];

    /**
     * @param array<string, string> $entities
     */
    public function __construct(
        private readonly array $entities,
    ) {
    }

    public static function getFormType(): string
    {
        return TextType::class;
    }

    public function load(array $ids): array
    {
        $this->loads[] = $ids;
        $loaded = [];

        foreach ($ids as $id) {
            if (isset($this->entities[(string) $id])) {
                $loaded[(string) $id] = new \ArrayObject(['name' => $this->entities[(string) $id]]);
            }
        }

        return $loaded;
    }

    public function normalize(object $entity): array
    {
        return $entity instanceof \ArrayObject ? $entity->getArrayCopy() : [];
    }
}
