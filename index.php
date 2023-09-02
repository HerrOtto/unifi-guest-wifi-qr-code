<?php

// Include required files
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/_global.php';
require_once __DIR__ . '/_unifi.class.php';

?><!DOCTYPE html>
<html>
<head>
    <title>WiFi QR Code</title>

    <!-- Load Google Fonts for styling -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&display=swap" rel="stylesheet">

    <style>
        /* Styling for the background gradient */
        html {
            background: linear-gradient(45deg, #6DD5FA, #6A0577AB);
        }

        /* Apply default font and font size */
        * {
            font-family: 'Roboto', sans-serif;
            font-size: 20pt;
        }

        /* Center content both horizontally and vertically */
        body, html {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Styling for the main content container */
        .center {
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column; /* Stack child elements vertically */
            align-items: center; /* Center children horizontally */
            justify-content: center; /* Center children vertically */
            height: 100vh; /* Take up full viewport height */
        }

        /* Styling for the Polaroid container */
        .polaroid {
            width: 200px;
            background-color: black;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        /* Styling for the inner content of the Polaroid */
        .polaroid-inner {
            width: 200px;
            height: 200px;
            background-color: white;
        }

        /* Styling for the QR code image */
        .polaroid-img {
            width: 100%;
            height: auto;
        }

        /* Styling for the subtitle container */
        .subtitle {
            width: 200px;
            background-color: black;
            color: white;
            padding: 10px;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        /* Apply special font and rotation to the subtitle text */
        .subtitle span {
            font-family: 'Patrick Hand', cursive;
            transform: rotate(-15deg);
        }

        /* Manual WiFi details */
        .wifi-manual {
            margin-top:20px;
            border-radius: 10px;
            background-color:white;
            color: black;
            padding:10px;
        }

        /* Exception */
        .exception {
            background-color:red;
            color: white;
            padding:10px;
            width: 400px;
            border-radius: 10px;
        }

        /* Watermark */
        .copyright {
            position: fixed;
            bottom:10px;
            right:10px;
            color: white;
            font-size:10pt;
        }
    </style>
    <!-- Set page refresh interval to 1 hour -->
    <meta http-equiv="refresh" content="3600">
    <script>

        // Reload the page every hour using JavaScript
        setTimeout(function () {
            location.reload();
        }, 3600000);

        // Add event listener to reload on body click
        window.addEventListener('DOMContentLoaded', function () {
            document.body.addEventListener('click', function () {
                let uniqueToken = new Date().getTime();
                window.location.href = `${window.location.href.split('?')[0]}?nocache=${uniqueToken}`;
            });
        });

    </script>
</head>
<body>
<div class="center">

    <?php

    try {

        // Cached WiFi-Settings
        try {
            // Check for and load WiFi configuration from JSON
            if (!file_exists(__DIR__ . '/wifi.json')) {
                throw new Exception('JSON not found');
            }
            $wifi = json_decode(file_get_contents(__DIR__ . '/wifi.json'), JSON_OBJECT_AS_ARRAY);
            if (!array_key_exists('ssid', $wifi)) {
                throw new Exception('Invalid JSON missing: ssid');
            }
            if (!array_key_exists('psk', $wifi)) {
                throw new Exception('Invalid JSON missing: ssid');
            }
            if (!array_key_exists('last', $wifi)) {
                throw new Exception('Invalid JSON missing: ssid');
            }
        } catch (Exception $e) {
            // Set default WiFi configuration if not found
            $wifi = array(
                'ssid' => '',
                'psk' => '',
                'last' => 0
            );
        }

        // Create UniFI Client instance
        $unifi = new UniFI();

        // Fetch WiFi configuration (Max. once in 5 minutes)
        if (time() - $wifi['last'] > 5 * 60) {

            // Anmelden
            $res = $unifi->login(
                $config['ip'],
                $config['user'],
                $config['pass']
            );
            if ($res === false) {
                throw new Exception('Auth failed');
            }

            // Fetch list of WLAN networks
            $wifis = $unifi->get(
                '/proxy/network/v2/api/site/default/wlan/enriched-configuration'
            );
            if (count($config) < 1) {
                throw new Exception('No configuration found');
            }

            // Find and update WiFi configuration
            $wifiConfig = '';
            foreach ($wifis as $wifisI => $wifisData) {
                if (strpos($wifisData['configuration']['name'], $config['wifi-prefix']) !== 0) {
                    continue;
                }

                $wifi['ssid'] = $wifisData['configuration']['name'];
                $wifi['psk'] = $wifisData['configuration']['x_passphrase'];
                $wifi['last'] = time();

                // Attempt to save configuration to JSON
                try {
                    file_put_contents(__DIR__ . '/wifi.json', json_encode($wifi, true));
                } catch (Exception $e) {
                    // Ignore JSON save errors
                }
            }
        }

        // Check WiFi configuration and generate QR code
        if ($wifi['last'] == 0) {
            throw new Exception('Wifi configuration not found');
        } else if (time() - $wifi['last'] > 10 * 60 * 60) {
            throw new Exception('Wifi configuration expired (>10h)');
        }

        // QR code content
        $wifiConfig =
            'WIFI:' .
            'S:' . $wifi['ssid'] . ';' .
            'T:WPA;' .
            'P:' . $wifi['psk'] . ';;';

        // Display WiFi QR code and details
        print '<!-- WiFi details -->' . PHP_EOL;
        print '<div class="polaroid">' . PHP_EOL;
        print '        <div class="polaroid-inner">' . PHP_EOL;
        print '            <img class="polaroid-img" src="qr.php?config=' . base64_encode($wifiConfig) . '" width=200 height=200 alt="QR Code for WiFi">' . PHP_EOL;
        print '        </div>' . PHP_EOL;
        print '    </div>' . PHP_EOL;
        print '    <div class="subtitle"><span>Guest WiFi ;-)</span></div>' . PHP_EOL;
        print '    <div class="wifi-manual">' . PHP_EOL;
        print '        <div>WLAN: <strong>' . $wifi['ssid'] . '</strong></div>' . PHP_EOL;
        print '        <div>Key: <strong>' . $wifi['psk'] . '</strong></div>' . PHP_EOL;
        print '    </div>' . PHP_EOL;
        print '<!-- /WiFi details -->' . PHP_EOL;

    } catch (Exception $e) {
        // Handle exceptions and display error message
        print '<!-- Exception -->' . PHP_EOL;
        print '<div class="exception">' . PHP_EOL;
        print  '  <div style="margin-bottom:10px;"><strong>Exception :-(</strong></div>';
        print '   <div>' . $e->getMessage() . '</div>';
        print '</div>' . PHP_EOL;
        print '<!-- /Exception -->' . PHP_EOL;
    }

    ?>

    <!-- Please keep this, thank you :-) -->
    <div class="copyright">Copyright netzmal GmbH</div>

</div>
</body>
</html>
