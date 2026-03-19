<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OpenAiPublicationTextReviewer
{
    private const RESPONSES_API_URL = 'https://api.openai.com/v1/responses';

    public function __construct(
        #[Autowire('%env(bool:OPENAI_PUBLICATION_PREPARE_ENABLED)%')]
        private readonly bool $enabled,
        #[Autowire('%env(string:OPENAI_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(string:OPENAI_PUBLICATION_MODEL)%')]
        private readonly string $model,
        #[Autowire('%env(string:OPENAI_ORGANIZATION_ID)%')]
        private readonly string $organizationId,
        #[Autowire('%env(string:OPENAI_PROJECT_ID)%')]
        private readonly string $projectId,
        #[Autowire('%env(string:OPENAI_CAINFO_PATH)%')]
        private readonly string $caInfoPath,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled && '' !== trim($this->apiKey);
    }

    public function review(string $text): string
    {
        if (!$this->enabled) {
            throw new \RuntimeException('AI-подготовка текста отключена в конфигурации.');
        }

        if ('' === trim($this->apiKey)) {
            throw new \RuntimeException('Для AI-подготовки текста не задан OPENAI_API_KEY.');
        }

        $payload = [
            'model' => trim($this->model) !== '' ? $this->model : 'gpt-5-mini',
            'reasoning' => [
                'effort' => 'low',
            ],
            'instructions' => 'Ты редактор официальных публикаций учреждений культуры. Исправь орфографию, пунктуацию, грамматику и слегка выправь стиль текста на русском языке. Сохрани все факты, имена, даты, цифры и смысл. Ничего не выдумывай и не добавляй. Не делай текст длиннее без необходимости. Верни только готовый текст публикации без пояснений, кавычек и комментариев.',
            'input' => $text,
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        if ('' !== trim($this->organizationId)) {
            $headers[] = 'OpenAI-Organization: ' . trim($this->organizationId);
        }

        if ('' !== trim($this->projectId)) {
            $headers[] = 'OpenAI-Project: ' . trim($this->projectId);
        }

        $curl = curl_init(self::RESPONSES_API_URL);
        $options = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        $caInfoPath = trim($this->caInfoPath);
        if ('' !== $caInfoPath) {
            if (!is_file($caInfoPath)) {
                throw new \RuntimeException('Файл CA-сертификата для OpenAI не найден: ' . $caInfoPath);
            }

            $options[CURLOPT_CAINFO] = $caInfoPath;
        }

        curl_setopt_array($curl, $options);
        $rawResponse = curl_exec($curl);

        if (false === $rawResponse) {
            $error = curl_error($curl);
            $verifyResult = curl_getinfo($curl, CURLINFO_SSL_VERIFYRESULT);
            curl_close($curl);

            $hint = '';
            if (str_contains(mb_strtolower($error), 'certificate')) {
                $hint = ' При необходимости укажите путь к доверенному CA-сертификату в OPENAI_CAINFO_PATH.';
            }

            throw new \RuntimeException(sprintf(
                'Не удалось выполнить запрос к OpenAI: %s (SSL verify result: %s).%s',
                $error,
                (string) $verifyResult,
                $hint
            ));
        }

        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $response = json_decode($rawResponse, true);
        if ($httpCode >= 400 || !is_array($response)) {
            $message = is_array($response) ? ($response['error']['message'] ?? 'Неизвестная ошибка OpenAI API.') : 'Некорректный ответ OpenAI API.';
            throw new \RuntimeException((string) $message);
        }

        $result = $this->extractText($response);
        if ('' === trim($result)) {
            throw new \RuntimeException('OpenAI не вернул текст для публикации.');
        }

        return trim($result);
    }

    private function extractText(array $response): string
    {
        $outputText = $response['output_text'] ?? null;
        if (is_string($outputText) && '' !== trim($outputText)) {
            return $outputText;
        }

        $parts = [];
        foreach (($response['output'] ?? []) as $outputItem) {
            foreach (($outputItem['content'] ?? []) as $contentItem) {
                if (isset($contentItem['text']) && is_string($contentItem['text'])) {
                    $parts[] = $contentItem['text'];
                }
            }
        }

        return trim(implode("\n", $parts));
    }
}