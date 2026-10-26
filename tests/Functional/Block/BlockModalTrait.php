<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Block;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Calls of the block modal endpoints (BlockController), as the Stimulus canvas does.
 */
trait BlockModalTrait
{
    private const string BASE = '/admin/pages/blocks/';

    private function loginEditor(): void
    {
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view pages', 'edit pages']), 'admin');
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'fr');
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array<string, mixed>
     */
    private function form(string $key, array $parameters = []): array
    {
        $this->client->request('POST', self::BASE . $key . '/form', $parameters);
        self::assertResponseIsSuccessful();

        return $this->json();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function validate(string $key, array $data, string $uid = 'block-1'): array
    {
        $token = new Crawler($this->form($key)['html'])->filter('input[name="data[_token]"]')->attr('value');
        $this->client->request('POST', self::BASE . $key . '/validate', ['uid' => $uid, 'data' => [...$data, '_token' => $token]]);

        return $this->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        $json = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($json);

        return $json;
    }
}
