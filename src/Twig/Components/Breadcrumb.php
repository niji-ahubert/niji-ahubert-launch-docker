<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Breadcrumb
{
    public string $label = 'success';
    public ?string $path = null;
}
