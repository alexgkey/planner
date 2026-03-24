<?php

namespace App\Service;

use App\Entity\EventReportPublication;
use App\Entity\Photo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class VkPublicationClient
{
    private const VK_API_URL = 'https://api.vk.com/method/';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $kernelProjectDir,
        #[Autowire('%env(bool:VK_PUBLICATION_ENABLED)%')]
        private readonly bool $vkEnabled,
        #[Autowire('%env(string:VK_GROUP_TOKEN)%')]
        private readonly string $groupToken,
        #[Autowire('%env(string:VK_GROUP_ID)%')]
        private readonly string $groupId,
        #[Autowire('%env(string:VK_API_VERSION)%')]
        private readonly string $apiVersion,
        #[Autowire('%env(string:VK_CAINFO_PATH)%')]
        private readonly string $caInfoPath,
    ) {
    }

    /**
     * @return array{message_id:?string}
     */
    public function publish(EventReportPublication $publication): array
    {
        if (!$this->vkEnabled) {
            throw new \RuntimeException('Публикация во ВКонтакте временно отключена в конфигурации.');
        }

        if ('' === trim($this->groupToken) || '' === trim($this->groupId)) {
            throw new \RuntimeException('Для публикации во ВКонтакте не заданы VK_GROUP_TOKEN или VK_GROUP_ID.');
        }

        $text = trim((string) $publication->getPreparedText());
        if ('' === $text) {
            throw new \RuntimeException('Нельзя опубликовать пустой текст.');
        }

        $attachments = [];
        foreach (($publication->getEventReport()?->getPhotos()->toArray() ?? []) as $photo) {
            if ($photo instanceof Photo && null !== $photo->getImageName()) {
                $attachments[] = $this->uploadWallPhoto($photo);
            }
        }

        $response = $this->callMethod('wall.post', [
            'owner_id' => -1 * (int) $this->groupId,
            'from_group' => 1,
            'message' => $text,
            'attachments' => [] !== $attachments ? implode(',', $attachments) : null,
        ]);

        return [
            'message_id' => isset($response['post_id']) ? (string) $response['post_id'] : null,
        ];
    }

    private function uploadWallPhoto(Photo $photo): string
    {
        $uploadServer = $this->callMethod('photos.getWallUploadServer', [
            'group_id' => (int) $this->groupId,
        ]);

        $uploadUrl = $uploadServer['upload_url'] ?? null;
        if (!is_string($uploadUrl) || '' === $uploadUrl) {
            throw new \RuntimeException('VK не вернул upload_url для фотографии.');
        }

        $filePath = $this->kernelProjectDir . '/public/uploads/photos/' . $photo->getImageName();
        if (!is_file($filePath)) {
            throw new \RuntimeException('Файл фотографии для VK не найден: ' . $filePath);
        }

        $uploadResponse = $this->rawRequest($uploadUrl, [
            'photo' => new \CURLFile($filePath),
        ], false);

        $savedPhotos = $this->callMethod('photos.saveWallPhoto', [
            'group_id' => (int) $this->groupId,
            'photo' => $uploadResponse['photo'] ?? null,
            'server' => $uploadResponse['server'] ?? null,
            'hash' => $uploadResponse['hash'] ?? null,
        ]);

        $savedPhoto = $savedPhotos[0] ?? null;
        if (!is_array($savedPhoto) || !isset($savedPhoto['owner_id'], $savedPhoto['id'])) {
            throw new \RuntimeException('VK не вернул данные сохраненной фотографии.');
        }

        return sprintf('photo%s_%s', $savedPhoto['owner_id'], $savedPhoto['id']);
    }

    private function callMethod(string $method, array $params): array
    {
        $params = array_filter($params, static fn (mixed $value): bool => null !== $value && '' !== $value);
        $params['access_token'] = $this->groupToken;
        $params['v'] = trim($this->apiVersion) !== '' ? $this->apiVersion : '5.199';

        $response = $this->rawRequest(self::VK_API_URL . $method, $params, true);

        if (isset($response['error'])) {
            $message = $response['error']['error_msg'] ?? 'Неизвестная ошибка VK API.';
            throw new \RuntimeException((string) $message);
        }

        if (!isset($response['response']) || !is_array($response['response'])) {
            throw new \RuntimeException('Некорректный ответ VK API.');
        }

        return $response['response'];
    }

    private function rawRequest(string $url, array $fields, bool $urlEncodeFields): array
    {
        $curl = curl_init($url);
        $options = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        $caInfoPath = trim($this->caInfoPath);
        if ('' !== $caInfoPath) {
            if (!is_file($caInfoPath)) {
                throw new \RuntimeException('Файл CA-сертификата для VK не найден: ' . $caInfoPath);
            }

            $options[CURLOPT_CAINFO] = $caInfoPath;
        }

        $options[CURLOPT_POSTFIELDS] = $urlEncodeFields ? http_build_query($fields) : $fields;
        curl_setopt_array($curl, $options);
        $rawResponse = curl_exec($curl);

        if (false === $rawResponse) {
            $error = curl_error($curl);
            $verifyResult = curl_getinfo($curl, CURLINFO_SSL_VERIFYRESULT);
            curl_close($curl);
            throw new \RuntimeException(sprintf('Не удалось выполнить запрос к VK: %s (SSL verify result: %s).', $error, (string) $verifyResult));
        }

        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $response = json_decode($rawResponse, true);
        if ($httpCode >= 400 || !is_array($response)) {
            throw new \RuntimeException('Некорректный ответ сервера VK.');
        }

        return $response;
    }
}