<?php

declare(strict_types=1);

namespace App\Services\DockerCompose;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Yaml\Dumper;

class DockerComposeFileManipulator
{
    public const COMPOSE_FILE_VERSION = '3.7';
    private YamlSourceManipulator $manipulator;

    public function initialize(?string $contents = null): void
    {
        if (null === $contents) {
            $this->manipulator = new YamlSourceManipulator(
                new Dumper()->dump($this->getBasicStructure(), 2),
            );
        } else {
            $this->manipulator = new YamlSourceManipulator($contents);
        }

        $this->checkComposeFileVersion();
    }

    /**
     * @return array<string, array<int|string, string>|string>
     */
    public function getComposeData(): array
    {
        return $this->manipulator->getData();
    }

    public function getDataString(): string
    {
        return $this->manipulator->getContents();
    }

    public function serviceExists(string $name): bool
    {
        $data = $this->manipulator->getData();

        if (\array_key_exists('services', $data)) {
            return \array_key_exists($name, $data['services']);
        }

        return false;
    }

    public function setGlobalNetworkComposeData(string $network): void
    {
        $data = $this->manipulator->getData();

        $data['networks']['traefik']['external'] = true;
        $data['networks']['traefik']['name'] = $network;

        $this->manipulator->setData($data);
    }

    public function setGlobalVolumeComposeData(string $volume, string $name): void
    {
        $data = $this->manipulator->getData();
        $data['volumes'][$volume]['name'] = $name;

        $this->manipulator->setData($data);
    }

    /**
     * @param array<string, array<int|string, string>|string> $details
     */
    public function addDockerService(?string $name, array $details): void
    {
        if (null === $name) {
            return;
        }

        $data = $this->manipulator->getData();

        $data['services'][$name] = $details;

        $this->manipulator->setData($data);
    }

    public function removeDockerService(string $name): void
    {
        $data = $this->manipulator->getData();

        unset($data['services'][$name]);

        $this->manipulator->setData($data);
    }

    /**
     * @param array<string, array<int|string, string>|string> $ports
     */
    public function exposePorts(?string $service, array $ports): void
    {
        if (null === $service) {
            return;
        }

        $portData = [];
        $portData[] = \sprintf('%s To allow the host machine to access the ports below, modify the lines below.', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);
        $portData[] = \sprintf('%s For example, to allow the host to connect to port 3306 on the container, you would change', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);
        $portData[] = \sprintf('%s "3306" to "3306:3306". Where the first port is exposed to the host and the second is the container port.', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);
        $portData[] = \sprintf('%s See https://docs.docker.com/compose/compose-file/compose-file-v3/#ports for more information.', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);

        foreach ($ports as $port) {
            $portData[] = $port;
        }

        $data = $this->manipulator->getData();

        $data['services'][$service]['ports'] = $portData;

        $this->manipulator->setData($data);
    }

    public function addVolume(string $service, string $hostPath, string $containerPath): void
    {
        $data = $this->manipulator->getData();

        $data['services'][$service]['volumes'][] = \sprintf('%s:%s', $hostPath, $containerPath);

        $this->manipulator->setData($data);
    }

    /**
     * @return array<string, array<int|string, string>|string>
     */
    private function getBasicStructure(string $version = self::COMPOSE_FILE_VERSION): array
    {
        return [
            'version' => $version,
            'networks' => [],
            'volumes' => [],
            'services' => [],
        ];
    }

    private function checkComposeFileVersion(): void
    {
        $data = $this->manipulator->getData();

        if (empty($data['version'])) {
            throw new RuntimeCommandException('compose.yaml file version is not set.');
        }

        if (2.0 > (float) $data['version']) {
            throw new RuntimeCommandException(\sprintf('compose.yaml version %s is not supported. Please update your compose.yaml file to the latest version.', $data['version']));
        }
    }
}
