<?php

declare(strict_types=1);

/**
 * Get a renderer for a diff view.
 *
 * Note: The parameters are case sensitive and must match the PSR-4 namespace rules.
 *
 * @param   string  $diffType    Type of diff view.
 * @param   string  $outputType  Type of output.
 *
 * @return object Diff-view Renderer.
 * @throws Exception When given an invalid diffType or the outputType isn't available for this diffType.
 */
function getRenderer(string $diffType, string $outputType): object
{
    $validCombos = [
        'Context'    => ['Text'],
        'SideBySide' => ['Html'],
        'Unified'    => ['Html', 'Text', 'Cli'],
        'Inline'     => ['Html', 'Text', 'Cli'],
    ];

    if (!array_key_exists($diffType, $validCombos) || !in_array($outputType, $validCombos[$diffType])) {
        // TODO: Throw appropriate Exception
        throw new Exception('No renderer available!');
    }

    if ($outputType == 'Cli') {
        $outputType = 'Text';
        $diffType   .= 'Cli';
    }

    $FQCN = "\jblond\Diff\Renderer\\$outputType\\$diffType";

    return new $FQCN();
}

/**
 * Send HTML content to the output buffer.
 *
 * A helper function which sends raw cache controlling http headers along with content.
 * If the header parameter isn't empty, it's succeeded by a html line break.
 *
 * Sending the header is always followed by sending the content.
 *
 * @param   string  $header
 * @param   string  $content
 */
function sendHTML(string $header, string $content)
{
    // Disable cache.
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Echo header if not empty.
    if ($header != '') {
        echo "$header<br>";
    }
    // Send content to the output buffer and exit script.
    echo $content;
    exit;
}

/**
 * Split a camelCased string into space separate words.
 *
 * @param   mixed  $input  The string to convert.
 *
 * @return string The split string.
 */
function splitCamelCase($input): string
{
    return ucfirst(
        implode(
            ' ',
            preg_split(
                '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
                (string)$input,
                -1, /* no limit for replacement count */
                PREG_SPLIT_NO_EMPTY /*don't return empty elements*/
                | PREG_SPLIT_DELIM_CAPTURE /*don't strip anything from output array*/
            )
        )
    );
}

/**
 * Stringify an array.
 *
 * The key-value pairs are concatenated by a space.
 * Camelcase words in keyNames are converted to separate words.
 *
 * By default, keyNames which aren't of type string aren't included in the returned value.
 * Set the second parameter to true to include them.
 *
 * @param   array  $array           Array to stringify.
 * @param   bool   $includeAllKeys  True to include non string keyNames.
 *
 * @return string Array as string.
 * @see splitCamelCase()
 */
function arrayToStr(array $array, bool $includeAllKeys = false): string
{
    $returnValue = '';
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $value = htmlspecialchars(arrayToStr($value, $includeAllKeys));
        }
        if (is_bool($value)) {
            $value = $value ? 'True' : 'False';
        }

        if (is_string($key) || $includeAllKeys) {
            $returnValue .= htmlspecialchars(splitCamelCase($key)) . ': ';
        }

        $returnValue .= "$value, ";
    }

    return substr("$returnValue", 0, -2);
}

/**
 * Get the version of the Diff package which was released last at github.
 *
 * The return value is always a two-element array.
 * The first element always contains an empty string.
 * The second element contains the version which is received from the github API.
 *
 * @see https://docs.github.com/en/free-pro-team@latest/rest
 * @return string[]
 */
function getLatestRelease(): array
{
    $ch = curl_init('https://api.github.com/repos/JBlond/php-diff/releases/latest');

    curl_setopt($ch, CURLOPT_USERAGENT, 'php-diff');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_error($ch)) {
        curl_close($ch);

        return ['', 'Unable to retrieve version!'];
    }

    curl_close($ch);

    preg_match('/"tag_name":"(.*?)"/', $response, $returnValue);

    return ['', $returnValue[1]];
}

/**
 * Get the version of the php-diff composer-package which is included to the current project.
 *
 * The version is extracted from the class level docblock at of the Diff class.
 *
 * The return value is always a two-element array.
 * The first element always contains an empty string.
 * The second element contains the version which is extracted from the Diff class as string.
 *
 * @return string[] An two element array containing the version.
 */
function getDemoRelease(): array
{
    $library = file_get_contents(__DIR__ . '/../vendor/jblond/php-diff/lib/jblond/Diff.php');
    if ($library === false) {
        return ['', '?'];
    }

    if (!preg_match('/@version\s*(.*)/m', $library, $returnValue)) {
        return ['', '?'];
    }

    return ['', $returnValue[1]];
}

/**
 * Get the name of a ANSI color which is closest to a given Hex color.
 *
 * @param   string  $hexColor    Hex color code of the wanted color.
 * @param   bool    $foreGround  True to get foreground color name, False for background color name.
 *
 * @return string Name of closest ANSI color.
 * @throws Exception When the hex formatted color is invalid.
 */
function getAnsiColor(string $hexColor, $foreGround = true): string
{
    // Predefined colors.
    $foregroundColors = [
        'black'        => '#000000',
        'dark_gray'    => '#808080',
        'red'          => '#800000',
        'light_red'    => '#FF0000',
        'green'        => '#008000',
        'light_green'  => '#00FF00',
        'brown'        => '#808000',
        'yellow'       => '#FFFF00',
        'blue'         => '#000080',
        'light_blue'   => '#0000FF',
        'purple'       => '#800080',
        'light_purple' => '#FF00FF',
        'cyan'         => '#008080',
        'light_cyan'   => '#00FFFF',
        'light_gray'   => '#C0C0C0',
        'white'        => '#FFFFFF',
    ];

    $backgroundColors = [
        'black'      => '#000000',
        'red'        => '#800000',
        'green'      => '#008000',
        'yellow'     => '#FFFF00',
        'blue'       => '#000080',
        'magenta'    => '#FF00FF',
        'cyan'       => '#008080',
        'light_gray' => '#C0C0C0',
    ];

    // Switch color names when requesting backgroundColor.
    $cliColors = $foreGround ? $foregroundColors : $backgroundColors;

    // Convert requested color into RGB components.
    $hexColor   = preg_replace('/[^[:xdigit:]]/', '', $hexColor);
    $colorToHex = [];
    switch (strlen($hexColor)) {
        case 3:
            for ($iterator = 0; $iterator < 3; $iterator++) {
                $colorToHex[$iterator] = hexdec($hexColor[$iterator] . $hexColor[$iterator]);
            }
            break;
        case 6:
            $colorValue    = hexdec($hexColor);
            $colorToHex[0] = 0xFF & ($colorValue >> 0x10);
            $colorToHex[1] = 0xFF & ($colorValue >> 0x8);
            $colorToHex[2] = 0xFF & $colorValue;
            break;
        default:
            // TODO: Throw appropriate Exception
            throw new Exception('Invalid color format!');
    }

    // Calculate distance from requested color to defined colors.
    $distances = [];
    foreach ($cliColors as $name => $value) {
        $colorValue      = hexdec($value);
        $definedColor[0] = 0xFF & ($colorValue >> 0x10);
        $definedColor[1] = 0xFF & ($colorValue >> 0x8);
        $definedColor[2] = 0xFF & $colorValue;

        $distances[$name] = sqrt(
            pow($colorToHex[0] - $definedColor[0], 2) +
            pow($colorToHex[1] - $definedColor[1], 2) +
            pow($colorToHex[2] - $definedColor[2], 2)
        );
    }

    // Return name of the closest pre-defined color.
    return current(array_keys($distances, min($distances)));
}

/**
 * Convert ANSI escape codes for colors to HTML tags with color styling.
 *
 * @param   string  $string  Text to convert.
 *
 * @return string Converted text.
 */
function AnsiToHtml(string $string): string
{
    $foreGroundColors = [
        '0;30' => '#000000',
        '5;30' => '#808080',
        '0;31' => '#800000',
        '1;31' => '#FF0000',
        '0;32' => '#008000',
        '1;32' => '#00FF00',
        '0;33' => '#808000',
        '1;33' => '#FFFF00',
        '0,34' => '#000080',
        '1;34' => '#0000FF',
        '0;35' => '#800080',
        '1;35' => '#FF00FF',
        '0;36' => '#008080',
        '1;36' => '#00FFFF',
        '0;37' => '#C0C0C0',
        '1;37' => '#FFFFFF',
    ];
    $backGroundColors = [
        '40' => '#000000',
        '41' => '#FF0000',
        '42' => '#00FF00',
        '43' => '#FFFF00',
        '44' => '#0000FF',
        '45' => '#FF00FF',
        '46' => '#008080',
        '47' => '#C0C0C0',
    ];

    $patterns     = [];
    $replacements = [];

    // Foreground colors.
    foreach ($foreGroundColors as $cli => $hex) {
        $patterns[]     = "/\x1b\[${cli}m(.*?)\x1b\[0m/";
        $replacements[] = '<span style="color: ' . $hex . '">$1</span>';
    }

    $string = preg_replace($patterns, $replacements, $string);

    // Background colors.
    foreach ($backGroundColors as $cli => $hex) {
        $patterns[]     = "/\x1b\[${cli}m(.*?)\x1b\[0m/";
        $replacements[] = '<span style="background-color: ' . $hex . '">$1</span>';
    }

    $string = preg_replace($patterns, $replacements, $string);

    // Background colors with foreground color.
    foreach ($backGroundColors as $cli => $hex) {
        $patterns[]     = "/\x1b\[${cli}m(.*?<\/span>)/";
        $replacements[] = '<span style="background-color: ' . $hex . '">$1</span>';
    }

    return preg_replace($patterns, $replacements, $string);
}
