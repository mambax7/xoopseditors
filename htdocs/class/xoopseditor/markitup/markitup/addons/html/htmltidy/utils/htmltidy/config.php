<?php
// Quick Reference
// HTML Tidy Configuration Options
// http://tidy.sourceforge.net/docs/quickref.html
$config = [
    'show-body-only'     => true,
    'wrap'               => 0,
    'tab-size'           => 2,
    'indent-spaces'      => 2,
    'indent'             => 'auto',
    'logical-emphasis'   => true,
    'fix-uri'            => true,
    'quote-ampersand'    => true,
    'enclose-block-text' => true,
    'drop-empty-paras'   => true,
    'output-xhtml'       => true,
    'vertical-space'     => true
];

$excludedWarnings = [
    'missing <!DOCTYPE> declaration',
    "inserting missing 'title' element",
    'inserting implicit <body>',
    "plain text isn't allowed in <head> elements"
];

