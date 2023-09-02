<?php

// Define the UniFI class
class UniFI
{
    private $ip = '';
    private $user = '';
    private $password = '';

    private $login = false;
    private $token = false;
    private $csrfToken = false;

    /**
     * Parse cookies from the response headers.
     *
     * @param string $headers The response headers containing cookies.
     * @return array Associative array containing parsed cookies.
     */
    private function _parseCookies($headers)
    {
        $cookies = array();

        // Split header lines into an array
        $headerLines = explode("\r\n", $headers);

        // Iterate through each header line
        foreach ($headerLines as $line) {
            if (stripos($line, 'Set-Cookie:') === 0) {
                // Extract the cookie part after 'Set-Cookie:'
                $cookieLine = trim(substr($line, strlen('Set-Cookie:')));

                // Split the cookie line into parts based on ';'
                $cookieParts = explode(';', $cookieLine);

                // Extract the first part as the cookie name and value
                $cookie = array_shift($cookieParts);
                $cookiePair = explode('=', $cookie, 2);
                $cookieName = urldecode(trim($cookiePair[0]));
                $cookieValue = isset($cookiePair[1]) ? urldecode(trim($cookiePair[1])) : '';

                // Add the cookie to the cookies array
                $cookies[$cookieName] = $cookieValue;
            }
        }

        return $cookies;
    }

    /**
     * Parse the CSRF token from the response headers.
     *
     * @param string $headers The response headers containing the CSRF token.
     * @return string|false The parsed CSRF token or false if not found.
     */
    private function _parseCsrfToken($headers)
    {
        $csrfToken = false;

        // Split header lines into an array
        $headerLines = explode("\r\n", $headers);

        // Iterate through each header line
        foreach ($headerLines as $line) {
            if (stripos($line, 'X-Csrf-Token:') === 0) {
                // Extract the CSRF token after 'X-Csrf-Token:'
                $csrfToken = trim(substr($line, strlen('X-Csrf-Token:')));
                break;
            }
        }

        return $csrfToken;
    }

    /**
     * Perform an HTTP request using cURL.
     *
     * @param string $ip The IP address of the UniFI cloud key.
     * @param string $path The path to the resource.
     * @param mixed $data The data to send with the request.
     * @param string $contentType The content type of the request.
     * @param array $params Query parameters for the URL.
     * @param array|false $cookies Cookies to include in the request.
     * @param string $method The HTTP method to use (GET, PUT, POST).
     * @return array Response details including code, cookies, body, and headers.
     * @throws Exception If an error occurs during the request.
     */
    private function _http($ip, $path, $data, $contentType = 'application/x-www-form-urlencoded', $params, $cookies = false, $method = 'POST')
    {
        // Initialize cURL handle based on HTTP method
        if ($method == 'GET') {
            $ch = curl_init('https://' . $ip . ':443' . $path . (!empty($params) ? '?' . http_build_query($params) : ''));
            if (!empty($data)) {
                throw new Exception('HTTP GET request does not support data');
            }
        } else if ($method == 'PUT') {
            $ch = curl_init('https://' . $ip . ':443' . $path . (!empty($params) ? '?' . http_build_query($params) : ''));
        } else if ($method == 'POST') {
            $ch = curl_init('https://' . $ip . ':443' . $path);
            if (!empty($params)) {
                throw new Exception('HTTP POST request does not support parameters');
            }
        } else {
            throw new Exception('Unknown HTTP method: ' . curl_error($method));
        }

        try {

            // Set request headers
            $header = array();
            $header = array_merge($header, array('Content-Type: ' . $contentType));
            if ($this->csrfToken !== false) {
                $header = array_merge($header, array('X-Csrf-Token: ' . $this->csrfToken));
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            // Set cURL options based on method
            if ($method == 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);

            // Allow self-signed certificates
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // Set cookies
            if ($cookies !== false) {
                $cookiesFormatted = array();
                foreach ($cookies as $name => $value) {
                    $cookiesFormatted[] = $name . '=' . $value;
                }
                $cookiesString = implode('; ', $cookiesFormatted);
                curl_setopt($ch, CURLOPT_COOKIE, $cookiesString);
            }

            // Perform the request and obtain response
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            $response = curl_exec($ch);

            // Extract response details
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);

            // Handle cURL errors
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
        } finally {
            curl_close($ch);
        }

        // Parse header data
        $cookies = $this->_parseCookies($responseHeaders);
        $csrfToken = $this->_parseCsrfToken($responseHeaders);
        if ($csrfToken !== false) {
            $this->csrfToken = $csrfToken;
        }

        return array('code' => $code, 'cookies' => $cookies, 'body' => $responseBody, 'header' => $responseHeaders);
    }

    /**
     * Logs in to the UniFi system using provided credentials.
     *
     * @param string $ip The IP address of the UniFi controller.
     * @param string $user The username for authentication.
     * @param string $password The password for authentication.
     * @return bool True if login is successful, false otherwise.
     * @throws Exception If an error occurs during login.
     */
    public function login($ip, $user, $password)
    {

        // Save provided parameters
        $this->ip = $ip;
        $this->user = $user;
        $this->password = $password;

        // Initialize the login result as false
        $this->login = false;

        // Perform an HTTP POST request to login
        $res = $this->_http(
            $this->ip,
            '/api/auth/login',
            array(
                'username' => $this->user,
                'password' => $this->password,
                'rememberMe' => false,
                'token' => ''
            ),
            'application/x-www-form-urlencoded',
            array(),
            array(),
            'POST'
        );

        // Check if the HTTP response code is not 200
        if ($res['code'] !== 200) {
            throw new Exception('Wrong HTTP result code - Expected: 200 Received: ' . $res['code']);
        }

        // Check if the 'TOKEN' cookie is missing from the response
        if (!array_key_exists('TOKEN', $res['cookies'])) {
            throw new Exception('No cookie TOKEN received');
        }

        // Parse the response body and save the token
        $this->login = json_decode($res['body']);
        $this->token = $res['cookies']['TOKEN'];

        // Return true if login was successful, otherwise false
        return $this->login !== false;
    }

    /**
     * Performs a GET request to the specified path with optional parameters.
     *
     * @param string $path The path for the GET request.
     * @param array $params Optional query parameters for the request.
     * @return array The decoded JSON response.
     * @throws Exception If an error occurs during the request.
     */
    public function get($path, $params = array())
    {
        // Perform an HTTP GET request
        $res = $this->_http(
            $this->ip,
            $path,
            array(),
            'application/json',
            $params,
            array(
                'TOKEN' => $this->token
            ),
            'GET'
        );

        // Check if the HTTP response code is not 200
        if ($res['code'] !== 200) {
            throw new Exception('Wrong HTTP result code - Expected: 200 Received: ' . $res['code']);
        }

        // Check if the 'TOKEN' cookie is missing from the response
        if (!array_key_exists('TOKEN', $res['cookies'])) {
            throw new Exception('No cookie TOKEN received');
        }

        // Return the decoded JSON response body
        return json_decode($res['body'], true);
    }

    /**
     * Performs a POST request to the specified path with optional data and parameters.
     *
     * @param string $path The path for the POST request.
     * @param array $data Optional JSON data to be sent in the request body.
     * @param array $params Optional query parameters for the request.
     * @return array The decoded JSON response.
     * @throws Exception If an error occurs during the request.
     */
    public function post($path, $data = array(), $params = array())
    {
        // Perform an HTTP POST request
        $res = $this->_http(
            $this->ip,
            $path,
            $data,
            'application/json',
            $params,
            array(
                'TOKEN' => $this->token
            ),
            'POST'
        );

        // Check if the HTTP response code is not 200
        if ($res['code'] !== 200) {
            throw new Exception('Wrong HTTP result code - Expected: 200 Received: ' . $res['code']);
        }

        // Check if the 'TOKEN' cookie is missing from the response
        if (!array_key_exists('TOKEN', $res['cookies'])) {
            throw new Exception('No cookie TOKEN received');
        }

        // Return the decoded JSON response body
        return json_decode($res['body'], true);
    }

    /**
     * Performs a PUT request to the specified path with optional data and parameters.
     *
     * @param string $path The path for the PUT request.
     * @param string $data Optional data to be sent in the request body.
     * @param string $contentType The content type of the data being sent (default: 'application/json').
     * @param array $params Optional query parameters for the request.
     * @return array The decoded JSON response.
     * @throws Exception If an error occurs during the request.
     */
    public function put($path, $data = '', $contentType = 'application/json', $params = array())
    {
        // Perform an HTTP PUT request
        $res = $this->_http(
            $this->ip,
            $path,
            $data,
            'application/json',
            $params,
            array(
                'TOKEN' => $this->token
            ),
            'PUT'
        );

        // Check if the HTTP response code is not 200
        if ($res['code'] !== 200) {
            throw new Exception('Wrong HTTP result code - Expected: 200 Received: ' . $res['code']);
        }

        // Check if the 'TOKEN' cookie is missing from the response
        if (!array_key_exists('TOKEN', $res['cookies'])) {
            throw new Exception('No cookie TOKEN received');
        }

        // Return the decoded JSON response body
        return json_decode($res['body'], true);
    }

}