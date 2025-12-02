<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    #[\Override]
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', $this->isInstanceof(...)),
        ];
    }

    /**
     * @param object|string $var
     * @param string        $class
     */
    public function isInstanceof($var, $class): bool
    {
        if (\is_object($var)) {
            return $var instanceof $class;
        }

        if (\is_string($var) && class_exists($var)) {
            return is_a($var, $class, true);
        }

        return false;
    }
}
