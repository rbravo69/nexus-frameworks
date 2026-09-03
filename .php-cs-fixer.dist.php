<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->append([__DIR__ . '/bin/nexus'])
    ->exclude(['build', 'vendor']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2x0' => true,
        'class_definition' => [
            'inline_constructor_arguments' => false,
            'space_before_parenthesis' => false,
        ],
        'declare_strict_types' => true,
        'function_declaration' => [
            'closure_fn_spacing' => 'one',
        ],
        'single_line_empty_body' => false,
        'trailing_comma_in_multiline' => [
            'after_heredoc' => false,
            'elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters'],
        ],
    ])
    ->setFinder($finder);
