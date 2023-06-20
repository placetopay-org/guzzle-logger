<?php

use PhpCsFixer\Finder;

$finder = Finder::create()
    ->notPath('vendor')
    ->in(getcwd())
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return \ShiftCS\styles($finder, [
    'no_unused_imports' => true,
]);
