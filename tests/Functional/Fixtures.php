<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Entity\Menu\Menu;
use Gingerminds\CmsBundle\Entity\Menu\MenuItem;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CoreBundle\Entity\Permission\Permission;
use Gingerminds\CoreBundle\Entity\Role\Role;
use Gingerminds\CoreBundle\Entity\Security\ApiToken;
use Gingerminds\CoreBundle\Entity\User\Contributor;
use Gingerminds\CoreBundle\Entity\User\User;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class Fixtures
{
    public const string PASSWORD = 'password123';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function permission(string $name): Permission
    {
        $permission = $this->entityManager->getRepository(Permission::class)->findOneBy(['name' => $name]);

        if (null === $permission) {
            $permission = new Permission();
            $permission->setName($name);
            $this->entityManager->persist($permission);
            $this->entityManager->flush();
        }

        return $permission;
    }

    /**
     * @param list<string> $permissions
     */
    public function role(string $name, array $permissions = []): Role
    {
        $role = new Role();
        $role->setName($name);

        foreach ($permissions as $permission) {
            $role->addPermission($this->permission($permission));
        }

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }

    /**
     * @param list<string> $permissions permissions granted through a dedicated role
     */
    public function user(string $email, array $permissions = [], bool $superAdmin = false): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));

        if ($superAdmin) {
            $user->addRoleEntity($this->role(User::SUPER_ADMIN_ROLE));
        } elseif ([] !== $permissions) {
            $user->addRoleEntity($this->role('Role of ' . $email, $permissions));
        }

        $contributor = new Contributor();
        $contributor->setFirstname('First');
        $contributor->setLastname(ucfirst(strtok($email, '@') ?: 'Last'));
        $contributor->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($contributor);
        $this->entityManager->flush();

        return $user;
    }

    public function token(User $user): string
    {
        $plain = ApiToken::generatePlainToken();
        $this->entityManager->persist(new ApiToken($user, 'test', $plain));
        $this->entityManager->flush();

        return $plain;
    }

    public function language(string $iso, ?string $label = null): Language
    {
        $language = new Language();
        $language->setIso($iso);
        $language->setLabel($label ?? strtoupper($iso));
        $this->entityManager->persist($language);
        $this->entityManager->flush();

        return $language;
    }

    /**
     * @param list<Language> $languages
     */
    public function site(string $code, array $languages = [], ?Language $default = null, string $url = 'https://example.com'): Site
    {
        $site = new Site();
        $site->setCode($code);
        $site->setUrl($url);
        $site->setLanguages($languages);
        $site->setDefaultLanguage($default);
        $this->entityManager->persist($site);
        $this->entityManager->flush();

        return $site;
    }

    public function menu(string $code, Site $site): Menu
    {
        $menu = new Menu();
        $menu->setCode($code);
        $menu->setSite($site);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        return $menu;
    }

    /**
     * @param array<string, string> $names name by language iso (languages created on demand)
     */
    public function menuItem(Menu $menu, string $code, array $names = [], ?MenuItem $parent = null, bool $active = true, int $position = 0, ?string $url = null): MenuItem
    {
        $item = new MenuItem();
        $item->setMenu($menu);
        $item->setCode($code);
        $item->setParent($parent);
        $item->setActive($active);
        $item->setPosition($position);

        foreach ($names as $iso => $name) {
            $language = $this->entityManager->getRepository(Language::class)->findOneBy(['iso' => $iso]) ?? $this->language($iso);
            $translation = new MenuItemTranslation();
            $translation->setLanguage($language);
            $translation->setName($name);
            $translation->setUrl($url);
            $item->addTranslation($translation);
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}
