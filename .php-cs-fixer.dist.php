<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache')
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'phpdoc_align' => false,
        'native_function_invocation' => false,
        'declare_strict_types' => false,
    ])
    ->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder);
