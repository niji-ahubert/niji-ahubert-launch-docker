<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$config = new Config();

return
    $config->setRiskyAllowed(true)
        ->setRules([
            '@Symfony' => true,
            '@Symfony:risky' => true,
            'dir_constant' => true,
            'heredoc_to_nowdoc' => true,
            'linebreak_after_opening_tag' => true,
            'modernize_types_casting' => true,
            'multiline_whitespace_before_semicolons' => true,
            'no_unreachable_default_argument_value' => true,
            'no_useless_return' => true,
            'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
            'phpdoc_order' => true,
            'declare_strict_types' => true,
            'doctrine_annotation_braces' => true,
            'doctrine_annotation_indentation' => true,
            'doctrine_annotation_spaces' => true,
            'psr_autoloading' => true,
            'no_php4_constructor' => true,
            'echo_tag_syntax' => true,
            'semicolon_after_instruction' => true,
            'align_multiline_comment' => true,
            'doctrine_annotation_array_assignment' => true,
            'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'package']],
            'list_syntax' => ['syntax' => 'short'],
            'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
            'phpdoc_to_comment' => false,
            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
            'global_namespace_import' => [
                'import_constants' => false,
                'import_functions' => false,
                'import_classes' => false,
            ],
            // Workaround for bug https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/5684
            // Still present in 3.5.0
            'native_constant_invocation' => [
                'strict' => false,
            ],

            // Array Notation
            'array_syntax' => ['syntax' => 'short'],
            'no_multiline_whitespace_around_double_arrow' => true,
            'no_whitespace_before_comma_in_array' => true,
            'trim_array_spaces' => true,
            'whitespace_after_comma_in_array' => true,

            // Basic
            'no_multiple_statements_per_line' => true,
            'no_trailing_comma_in_singleline' => true,

            // Casing
            'class_reference_name_casing' => true,

            // Cast Notation
            'cast_spaces' => ['space' => 'single'],

            // Class Notation
            'class_attributes_separation' => [
                'elements' => [
                    'method' => 'one',
                    'property' => 'only_if_meta',
                ],
            ],
            'class_definition' => true,
            'no_blank_lines_after_class_opening' => true,
            'no_null_property_initialization' => true,
            'ordered_class_elements' => true,
            'ordered_interfaces' => true,
            'protected_to_private' => true,
            'visibility_required' => ['elements' => ['property', 'method', 'const']],

            // Comment
            'single_line_comment_style' => true,

            // Control Structure
            'elseif' => true,
            'no_superfluous_elseif' => true,
            'no_unneeded_control_parentheses' => true,
            'no_useless_else' => true,
            'trailing_comma_in_multiline' => [
                'elements' => ['arguments', 'arrays', 'match', 'parameters'],
            ],
            'yoda_style' => true,
            'simplified_if_return' => true,

            // Import
            'no_unneeded_import_alias' => true,
            'no_unused_imports' => true,
            'ordered_imports' => true,
            'single_import_per_statement' => true,
            'single_line_after_imports' => true,

            // Operator
            'ternary_operator_spaces' => true,
            'no_useless_nullsafe_operator' => true,
            'increment_style' => ['style' => 'post'],

            // Whitespace
            'blank_line_before_statement' => ['statements' => ['continue', 'declare', 'return', 'throw', 'try']],
            'no_extra_blank_lines' => ['tokens' => ['extra', 'continue', 'curly_brace_block', 'return', 'parenthesis_brace_block', 'square_brace_block', 'throw', 'use']],
            'method_chaining_indentation' => true,
            'no_whitespace_in_blank_line' => true,
            'single_blank_line_at_eof' => true,
            'array_indentation' => true,
        ])
        ->setCacheFile(__DIR__ . '/.php_cs.cache')
        ->setFinder(Finder::create()->in(['src']));
