<?php

/*
 * @source https://gist.github.com/nikoskip/bcf58106f728a3e7b7f9dcb6edaa1e82
 */

$fontTypes = array('woff2', 'woff', 'ttf', 'svg', 'eot');
$gFontURL = 'http://fonts.googleapis.com/css?family=';

$uaFonts = array(
    'woff2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
    'woff' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/4.0; GTB7.4; InfoPath.3; SV1; .NET CLR 3.1.76908; WOW64; en-US)',
    'ttf' => 'Mozilla/5.0 (Linux; U; Android 2.2.1; en-ca; LG-P505R Build/FRG83) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
    'svg' => 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10',
    'eot' => 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)'
);

function curlGoogleFont($url, $ua) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 1,
        CURLOPT_VERBOSE => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => $ua
    ));

    if (($response = curl_exec($curl))) {
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);

        if (strrpos($header, 'Content-Type: text/css')) {
            return $response;
        }
    }
    
    return '';
}

function parseCss($css) {
    $fonts = array();
    $fontName = null;
    $fontStyle = null;
    $fontWeight = null;
    $url = null;

    foreach (preg_split('/\r\n|\n|\r/', $css) as $cssLine) {
        // We save the font-family name for EOT
        if (strpos($cssLine, 'font-family')) {
            preg_match("/'(.*?)'/i", $cssLine, $data);
            $fontName = $data[1];
        }
        if (strpos($cssLine, 'url')) {
            preg_match('/local\((.*?)\)/i', $cssLine, $data);
            if (count($data)) {
                $fontName = str_replace('\'', '', $data[1]);
            }
        }
        if (strpos($cssLine, 'src')) {
            preg_match('/url\((.*?)\)/i', $cssLine, $data);
             $url = $data[1];
        }
        if (strpos($cssLine, 'font-style')) {
            preg_match("/\: '?(.*?)'?;$/i", $cssLine, $data);
            $fontStyle = $data[1];
        }
        if (strpos($cssLine, 'font-weight')) {
            preg_match("/\: '?(.*?)'?;$/i", $cssLine, $data);
            $fontWeight = $data[1];
        }

        if ($fontName !== null && $url !== null) {
            $fonts[implode('-', array($fontName, $fontStyle, $fontWeight))] = $url;
            $fontStyle = null;
            $fontWeight = null;
            $fontName = null;
            $url = null;
        }
    }

    return $fonts;
}

if (isset($_POST['url'])) {
    $urlParts = parse_url($_POST['url']);
    parse_str($urlParts['query'], $queryParts);
    $gFontURL .= urlencode($queryParts['family']);
    $fontsDownloadLinks = array();

    foreach ($fontTypes as $fontType) {
        $content = curlGoogleFont($gFontURL, $uaFonts[$fontType]);
        $fontsDownloadLinks[$fontType] = parseCss($content);
    }

}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Download Google Fonts - Simple PHP Script</title>
</head>
<body>
    <p>Thanks for visiting. Please provide your Google Fonts URL to import in your CSS (example: http://fonts.googleapis.com/css?family=Cabin:500,700,500italic,700italic) and you will get links for download <?php echo implode(', ', $fontTypes); ?> fonts.</p>
    <form action="" method="post">
        Google Fonts URL: <input type="text" name="url" value="<?php if(isset($_POST['url'])) echo $_POST['url']; ?>">
        <input type="submit" value="Get fonts!">
    </form>

    <?php if (isset($fontsDownloadLinks)) { ?>
        <hr>
        <?php foreach ($fontsDownloadLinks as $font => $links) { ?>
            <strong><?php echo $font; ?></strong>

            <?php echo count($links) === 0  ? '<p>Not found</p>' : ''; ?>
            <table border="1" cellspacing="0" cellpadding="2">
                <tr>
                    <?php foreach ($links as $fontName => $link) { ?>
                        <td><a href="<?php echo $link; ?>"><?php echo $fontName; ?></a></td>
                    <?php } ?>
            </table>
            <?php
        }
    }
    ?>

    <hr>
    <a href="https://twitter.com/nikoskip">Contact</a> | <a href="https://gist.github.com/nikoskip/bcf58106f728a3e7b7f9dcb6edaa1e82">Download code</a>
</body>
</html>