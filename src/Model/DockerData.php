<?php

namespace App\Model;

final readonly class DockerData
{
    public function __construct(
        private string  $image_name,
        private string  $tag_version,
        private string  $from_statement,
        private ?string $extensions_selected = null,
        private ?int    $port = null,
    )
    {

    }

    public function getTagVersion(): string
    {
        return $this->tag_version;
    }


    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getImageName(): string
    {
        return $this->image_name;
    }

    public function getExtensionsSelected(): ?string
    {
        return $this->extensions_selected;
    }

    public function getFromStatement(): string
    {
        return $this->from_statement;
    }
}