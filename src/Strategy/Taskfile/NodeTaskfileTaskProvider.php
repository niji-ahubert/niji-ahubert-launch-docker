<?php

declare(strict_types=1);

namespace App\Strategy\Taskfile;

use App\Enum\ContainerType\ProjectContainer;
use App\Services\Taskfile\TaskfileTaskProviderInterface;

final readonly class NodeTaskfileTaskProvider implements TaskfileTaskProviderInterface
{
    public function supports(ProjectContainer $containerType): bool
    {
        return ProjectContainer::NODE === $containerType;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getTasks(): array
    {
        return [
            'npm-audit' => [
                'desc' => '🔍 Run npm audit',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔒 Running Security Check..."',
                    '{{.DOCKER_ADMIN_COMP}} exec {{.SERVICE_NAME}} npm audit',
                ],
            ],
            'npm-outdated' => [
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
                    '{{.DOCKER_ADMIN_COMP}} exec {{.SERVICE_NAME}} npm outdated',
                ],
            ],
            'eslint' => [
                'desc' => '🔍 Run ESLint',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔍 Running ESLint..."',
                    '{{.DOCKER_ADMIN_COMP}} exec {{.SERVICE_NAME}} npm run lint',
                ],
            ],
            'prettier' => [
                'desc' => '🔍 Run Prettier',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔍 Running Prettier..."',
                    '{{.DOCKER_ADMIN_COMP}} exec {{.SERVICE_NAME}} npm run format',
                ],
            ],
            'typecheck' => [
                'desc' => '🔍 Run TypeScript type checking',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🔍 Running TypeScript Type Check..."',
                    '{{.DOCKER_ADMIN_COMP}} exec {{.SERVICE_NAME}} npm run type-check',
                ],
            ],
            'test' => [
                'desc' => '🧪 Run tests',
                'silent' => true,
                'internal' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => '{{if .SERVICE_NAME}}echo {{.SERVICE_NAME}}{{else}}task service-running | gum choose{{end}}',
                    ],
                ],
                'cmds' => [
                    'echo "🧪 Running Tests..."',
                    '{{.DOCKER_ADMIN_COMP}} exec {{.SERVICE_NAME}} npm run test',
                ],
            ],
            'qa-node' => [
                'desc' => '🔍 Run quality assurance tools for Node.js',
                'silent' => true,
                'vars' => [
                    'SERVICE_NAME' => [
                        'sh' => 'task service-running | gum choose',
                    ],
                ],
                'cmds' => [
                    [
                        'task' => 'eslint',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'prettier',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'typecheck',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'test',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'security',
                    ],
                    [
                        'task' => 'npm-audit',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                    [
                        'task' => 'npm-outdated',
                        'vars' => ['SERVICE_NAME' => '{{.SERVICE_NAME}}'],
                    ],
                ],
            ],
        ];
    }
}
