<?php

namespace App\Service;

use App\Entity\EventReportPublication;
use App\Entity\Photo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TelegramPublicationClient
{
    private const TELEGRAM_API_URL = 'https://api.telegram.org';
    private const CAPTION_LIMIT = 1024;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $kernelProjectDir,
        #[Autowire('%env(bool:TELEGRAM_PUBLICATION_ENABLED)%')]
        private readonly bool $telegramEnabled,
        #[Autowire('%env(string:TELEGRAM_BOT_TOKEN)%')]
        private readonly string $botToken,
        #[Autowire('%env(string:TELEGRAM_CHAT_ID)%')]
        private readonly string $chatId,
        #[Autowire('%env(string:TELEGRAM_CAINFO_PATH)%')]
        private readonly string $telegramCaInfoPath,
    ) {
    }

    /**
     * @return array{message_id:?string}
     */
    public function publish(EventReportPublication $publication): array
    {
        if (!$this->telegramEnabled) {
            throw new \RuntimeException('Публикация в Telegram отключена в конфигурации.');
        }

        if ('' === trim($this->botToken) || '' === trim($this->chatId)) {
            throw new \RuntimeException('Для Telegram не заданы bot token или chat id.');
        }

        $text = trim((string) $publication->getPreparedText());
        if ('' === $text) {
            throw new \RuntimeException('Нельзя опубликовать пустой текст.');
        }

        $photos = $publication->getEventReport()?->getPhotos()->toArray() ?? [];
        $photos = array_values(array_filter($photos, static fn (mixed $photo): bool => $photo instanceof Photo && null !== $photo->getImageName()));

        if ([] === $photos) {
            return ['message_id' => $this->sendMessage($text)];
        }

        $messageId = $this->sendMediaGroup($photos, $text);

        if (mb_strlen($text) > self::CAPTION_LIMIT) {
            $this->sendMessage($text);
        }

        return ['message_id' => $messageId];
    }

    /**
     * @param Photo[] $photos
     */
    private function sendMediaGroup(array $photos, string $text): ?string
    {
        $media = [];
        $fields = ['chat_id' => $this->chatId];
        $shortCaption = mb_strlen($text) <= self::CAPTION_LIMIT ? $text : null;

        foreach ($photos as $index => $photo) {
            $attachmentName = sprintf('photo_%d', $index + 1);
            $filePath = $this->kernelProjectDir . '/public/uploads/photos/' . $photo->getImageName();

            if (!is_file($filePath)) {
                continue;
            }

            $fields[$attachmentName] = new \CURLFile($filePath);
            $item = [
                'type' => 'photo',
                'media' => 'attach://' . $attachmentName,
            ];

            if (0 === $index && null !== $shortCaption) {
                $item['caption'] = $shortCaption;
            }

            $media[] = $item;
        }

        if ([] === $media) {
            return $this->sendMessage($text);
        }

        $fields['media'] = json_encode($media, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $response = $this->request('sendMediaGroup', $fields);

        if (!is_array($response) || [] === $response) {
            return null;
        }

        $firstMessage = $response[0] ?? null;

        return isset($firstMessage['message_id']) ? (string) $firstMessage['message_id'] : null;
    }

    private function sendMessage(string $text): ?string
    {
        $response = $this->request('sendMessage', [
            'chat_id' => $this->chatId,
            'text' => $text,
        ]);

        return isset($response['message_id']) ? (string) $response['message_id'] : null;
    }

    private function request(string $method, array $fields): mixed
    {
        $curl = curl_init(sprintf('%s/bot%s/%s', self::TELEGRAM_API_URL, $this->botToken, $method));

        $curlOptions = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        $caInfoPath = trim($this->telegramCaInfoPath);
        if ('' !== $caInfoPath) {
            if (!is_file($caInfoPath)) {
                throw new \RuntimeException('Файл CA-сертификата для Telegram не найден: ' . $caInfoPath);
            }

            $curlOptions[CURLOPT_CAINFO] = $caInfoPath;
        }

        curl_setopt_array($curl, $curlOptions);

        $rawResponse = curl_exec($curl);
        if (false === $rawResponse) {
            $error = curl_error($curl);
            $verifyResult = curl_getinfo($curl, CURLINFO_SSL_VERIFYRESULT);
            curl_close($curl);

            $hint = '';
            if (str_contains(mb_strtolower($error), 'self-signed certificate')) {
                $hint = ' Укажите путь к доверенному CA-сертификату в TELEGRAM_CAINFO_PATH.';
            }

            throw new \RuntimeException(sprintf(
                'Не удалось выполнить запрос к Telegram: %s (SSL verify result: %s).%s',
                $error,
                (string) $verifyResult,
                $hint
            ));
        }

        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $response = json_decode($rawResponse, true);

        if ($httpCode >= 400 || !is_array($response) || !($response['ok'] ?? false)) {
            $description = is_array($response) ? ($response['description'] ?? 'Неизвестная ошибка Telegram API.') : 'Некорректный ответ Telegram API.';
            throw new \RuntimeException((string) $description);
        }

        return $response['result'] ?? null;
    }
}