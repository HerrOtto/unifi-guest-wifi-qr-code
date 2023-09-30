<?php

// Include required files
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/_global.php';
require_once __DIR__ . '/_unifi.class.php';

foreach ($config as $configSiteName => $configSite) {
    try {

        // Create UniFI Client instance
        $unifi = new UniFI();

        // Login to UniFI
        $res = $unifi->login(
            $configSite['ip'],
            $configSite['user'],
            $configSite['pass']
        );
        if ($res === false) {
            throw new Exception('Auth failed');
        }

        // Fetch list of WLAN networks
        $wifis = $unifi->get(
            '/proxy/network/v2/api/site/default/wlan/enriched-configuration'
        );
        if (count($configSite) < 1) {
            throw new Exception('No configuration found');
        }

        // Change WLAN keys
        foreach ($wifis as $wifisI => $wifisData) {
            if (strpos($wifisData['configuration']['name'], $configSite['wifi-prefix']) !== 0) {
                continue;
            }

            // Generate new SSID and PSK
            $newSSID = $configSite['wifi-prefix'] . ' ' . date('ymd');
            $newPsk = generateRandomWord(5, 6) . ' ' . generateRandomWord(5, 6) . ' ' . generateRandomWord(5, 6);

            // Update WLAN configuration
            $res = $unifi->put(
                '/proxy/network/api/s/default/rest/wlanconf/' . $wifisData['configuration']['_id'],
                json_encode(array(
                    'name' => $newSSID,
                    'x_passphrase' => $newPsk,
                )),
                'application/json',
                array()
            );
        }

    } catch (Exception $e) {
        print 'Exception: ' . $e->getMessage();
    }
}
