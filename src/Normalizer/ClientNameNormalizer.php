<?php

declare(strict_types=1);

namespace App\Normalizer;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

readonly class ClientNameNormalizer
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function normalize(string $clientName): string
    {
        if (\in_array(trim($clientName), ['', '0'], true)) {
            return 'client-'.uniqid('', true);
        }

        $string = new UnicodeString($clientName);
        $normalized = $string
            ->trim()
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->replaceMatches('/-+/', '-')
            ->trim('-')
            ->truncate(50, '')
            ->toString();

        if ('' === $normalized || '0' === $normalized || \strlen($normalized) < 2) {
            $normalized = $this->slugger->slug($clientName)->toString();
            if (\strlen($normalized) > 50) {
                $normalized = substr($normalized, 0, 50);
                $normalized = rtrim($normalized, '-');
            }
        }

        if ('' === $normalized || '0' === $normalized || \strlen($normalized) < 2) {
            return 'client-'.uniqid('', true);
        }

        return $normalized;
    }
}
