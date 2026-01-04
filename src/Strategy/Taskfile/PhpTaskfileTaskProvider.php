<?php

declare(strict_types=1);

namespace App\Strategy\Taskfile;

use App\Enum\ContainerType\ProjectContainer;
use App\Services\Taskfile\TaskfileTaskProviderInterface;

final readonly class PhpTaskfileTaskProvider implements TaskfileTaskProviderInterface
{
    public function supports(ProjectContainer $containerType): bool
    {
        return ProjectContainer::PHP === $containerType;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getTasks(): array
    {
        return [
            'composer-audit' => [
                'desc' => '🔍 Run composer audit',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔒 Running Security Check..."',
                    'docker run --rm --interactive --tty --user $(id -u):$(id -g) --env GIT_SAFE_DIRECTORY=* --volume $PWD:/app composer audit',
                ],
            ],
            'composer-outdated' => [
                'desc' => '🔍 Check for outdated packages',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔒 Checking for outdated packages..."',
                    'docker run --rm --interactive --tty --user $(id -u):$(id -g) --env GIT_SAFE_DIRECTORY=* --volume $PWD:/app composer outdated',
                ],
            ],
            'phpstan' => [
                'desc' => '🔍 Run phpstan',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔍 Running PHPStan..."',
                    '{{.DOCKER_ADMIN_COMP}} exec -e XDEBUG_MODE=off {{.SERVICE_NAME}} vendor/bin/phpstan analyse src --memory-limit=-1',
                ],
            ],
            'rector' => [
                'desc' => '🔍 Run rector ',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔍 Running Rector... {{.SERVICE_NAME}}"',
                    '{{.DOCKER_ADMIN_COMP}} exec -e XDEBUG_MODE=off {{.SERVICE_NAME}} vendor/bin/rector process --config rector.php',
                ],
            ],
            'phpcsfixer' => [
                'desc' => '🔍 Run php-cs-fixer',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔍 Running PHP-CS-Fixer..."',
                    '{{.DOCKER_ADMIN_COMP}} exec -e XDEBUG_MODE=off {{.SERVICE_NAME}} vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php --diff',
                ],
            ],
            'qa' => [
                'desc' => '🔍 Run quality assurance tools',
                'silent' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => 'task service-running | gum choose',
                    ],
                ],
                'cmds' => [
                    [
                        'task' => 'rector',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'phpcsfixer',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'phpstan',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'security',
                    ],
                    [
                        'task' => 'composer-audit',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'composer-outdated',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                ],
            ],
        ];
    }
}
