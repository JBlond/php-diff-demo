<?php

use jblond\Diff;

// Include files and instantiate autoloader.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/functions.php';

// Respond to any other valid request than getDiff.
if (isset($_GET['request'])) {
    if ($_GET['request'] !== 'getDiff' && function_exists($_GET['request'])) {
        $result = call_user_func($_GET['request']);
        sendHTML($result[0], $result[1]);
    }
} else {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// Include two sample files for comparison.
$sampleA = file_get_contents('./resources/a.txt');
$sampleB = file_get_contents('./resources/b.txt');

// Options for the diff object.
$diffOptions = [
    'context'          => (int)($_GET['optionContext'] ?? 3),
    'trimEqual'        => ($_GET['optionTrimEqual'] ?? false) == "1",
    'ignoreWhitespace' => ($_GET['optionIgnoreWhitespace'] ?? false) == "1",
    'ignoreCase'       => ($_GET['optionIgnoreCase'] ?? false) == "1",
];

// Options for the renderer object.
$rendererOptions = [
    'format'          => $_GET['inputFormat'] ?? 'plain',
    'tabSize'         => (int)($_GET['optionTabSize'] ?? 4),
    'title1'          => $_GET['optionTitle1'] ?? 'Version 1',
    'title2'          => $_GET['optionTitle2'] ?? 'Version 2',

    // TODO: Should these be validated for being an array?
    'insertMarkers'   => $_GET['optionInsertMarkers'] ?? ['<ins>', '</ins>'],
    'deleteMarkers'   => $_GET['optionDeleteMarkers'] ?? ['<del>', '</del>'],
    'equalityMarkers' => $_GET['optionEqualityMarkers'] ?? ['', ''],

    'cliColor'     => ($_GET['optionCliColor'] ?? false) == "1",
    // TODO: Should these be validated for being an array?
    'insertColors' => $_GET['optionInsertColors'] ?? [],
    'deleteColors' => $_GET['optionDeleteColors'] ?? [],
];

try {
    // Get renderer.
    $diffType   = $_GET['diffType'] ?? null;
    $outputType = $_GET['outputType'] ?? null;

    if ($diffType === null || $outputType === null) {
        throw new \RuntimeException('Diff- or OutputType is undefined!');
    }

    if ($outputType === 'Cli' && $rendererOptions['cliColor']) {
        // Convert colors to ANSI colors
        $rendererOptions['insertColors'][0] = getAnsiColor($rendererOptions['insertColors'][0]);
        $rendererOptions['insertColors'][1] = getAnsiColor($rendererOptions['insertColors'][1], false);
        $rendererOptions['deleteColors'][0] = getAnsiColor($rendererOptions['deleteColors'][0]);
        $rendererOptions['deleteColors'][1] = getAnsiColor($rendererOptions['deleteColors'][1], false);
    }

    $renderer = getRenderer($diffType, $outputType);
    $renderer->setOptions($rendererOptions);

    // Instantiate the diff and compare the two samples.
    $diff       = new Diff($sampleA, $sampleB, $diffOptions);
    $diffResult = $diff->Render($renderer);

    // Format result
    if ($outputType == 'Text') {
        $diffResult = '<pre>' . htmlspecialchars($diffResult) . '</pre>';
    }

    if ($outputType == 'Cli') {
        $diffResult = '<pre class="cli">' . htmlspecialchars($diffResult) . '</pre>';
        $diffResult = AnsiToHtml($diffResult);
    }

    // Set diff-view header.
    $header = '<h2>' . splitCamelCase($diffType) . " &ndash; $outputType</h2>";
    $header .= 'Processing Options &ndash; <small class="text-secondary">' . arrayToStr($diffOptions) . '</small><br>';
    $header .= 'Renderer Options &ndash; <small class="text-secondary">' . arrayToStr($rendererOptions) . '</small><hr>';

    sendHTML(
        $header,
        $diff->isIdentical() ? 'No differences found.' : $diffResult
    );
} catch (Exception $e) {
    //TODO: Replace when all caught exceptions have an appropriate type/message.
    sendHTML(
        '<h2>Error</h2>',
        <<<HTML
        <div class="alert alert-danger text-center" role="alert">
            Sorry, the server ran into a problem while trying to process your request :o(!
        </div>
HTML
    );
}
