<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

/**
 * A node of an admin tree (menu items, page categories): see TreeNodes and AbstractTreeCrudController.
 */
interface TreeNodeInterface
{
    public function getId(): ?int;

    public function getParent(): ?self;
}
