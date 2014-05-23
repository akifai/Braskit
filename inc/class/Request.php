<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class RequestException extends Exception {}

class Request {
    public $get = array();
    public $post = array();
    public $cookie = array();
    public $files = array();
    public $server = array();
    public $env = array();

    public $ip = '127.0.0.1';
    public $time = 0; // not a sane default, we use time() if this fails
    public $microtime = 0;
    public $referrer = false;
    public $method = false;

    protected $protocol = '';
    protected $httpAuth = null;
    protected $hostname = '';
    protected $url = '';

    public function __construct() {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            // available since php 5.4
            $this->microtime = $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            // we lose accuracy, but it's a fair compromise
            $this->microtime = microtime(true);
        }

        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->env = $_ENV;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->time = $_SERVER['REQUEST_TIME'];

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->referrer = $_SERVER['HTTP_REFERER'];
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }

        if (get_magic_quotes_gpc()) {
            $this->unescapeMagicQuotes();
        }
    }

    /**
     * Retrieves a variable from the $_SERVER array.
     *
     * @return mixed The variable, or null if it doesn't exist.
     */
    public function getServerVar($key) {
        if (isset($this->server[$key])) {
            return $this->server[$key];
        }

        return null;
    }

    /**
     * Gets the protocol for the current request.
     *
     * @return string
     */
    public function getProtocol() {
        if ($this->protocol) {
            // do nothing
        } elseif ($this->getServerVar('HTTPS')) {
            $this->protocol = 'https';
        } else {
            $this->protocol = 'http';
        }

        return $this->protocol;
    }

    /**
     * Returns the HTTP Authentication information.
     *
     * @param boolean $getArray Return a null-padded array with the credentials?
     *
     * @return string|array Credentials
     */
    public function getHttpAuth($getArray = false) {
        if ($this->httpAuth === null) {
            $user = $this->getServerVar('PHP_AUTH_USER');
            $pass = $this->getServerVar('PHP_AUTH_PW');

            $auth = array();

            // username
            if (strlen($user)) {
                $auth[] = $user;

                // password
                if (strlen($pass)) {
                    $auth[] = $pass;
                }
            }

            $this->httpAuth = $auth;
        }

        if ($getArray) {
            return array_pad($this->httpAuth, 2, false);
        }

        return implode(':', $this->httpAuth);
    }

    /**
     * Gets the hostname for the current request. If the request port is
     * non-standard, that gets tacked on too.
     *
     * @return string Hostname
     */
    public function getHostName() {
        if (!$this->hostname) {
            $host = $this->getServerVar('HTTP_HOST')
                ?: $this->getServerVar('SERVER_NAME')
                ?: 'localhost';

            $this->hostname = $host;

            $proto = $this->getProtocol();
            $port = $this->getServerVar('SERVER_PORT');

            // tack on the port number if applicable
            if (
                !preg_match('/:\d+$/', $host) && (
                    $proto === 'https' && $port != 443 ||
                    $proto === 'http' && $port != 80
                )
            ) {
                $this->hostname = ":$port";
            }
        }

        return $this->hostname;
    }

    /**
     * Returns the script name.
     *
     * @deprecated
     * @return string
     */
    public function getScriptName() {
        $scriptname = $this->getServerVar('SCRIPT_NAME');

        return (string)$scriptname;
    }

    /**
     * @return string URL of the current request.
     */
    public function getUrl() {
        if (!$this->url) {
            $protocol = $this->getProtocol();
            $auth = $this->getHttpAuth();
            $host = $this->getHostName();
            $uri = $this->getServerVar('REQUEST_URI');

            if (strlen($auth)) {
                $auth .= '@';
            }

            $this->url = "$protocol://{$auth}{$host}{$uri}";
        }

        return $this->url;
    }

    /**
     * Gets an array containing the details of the uploaded file.
     *
     * @return boolean|array Array containing the file details or false if no
     *                       such file exists.
     */
    public function getUpload($key) {
		if (
            isset($this->files[$key]['name']) &&
            $this->files[$key]['name'] !== ''
        ) {
            $this->validateUpload($this->files[$key]);
            return $this->files[$key];
        }

        return false;
	}

    /**
     * Gets an array of arrays containing details of the uploaded files.
     *
     * The returned array of arrays is more sane than PHP's way of doing it. The
     * structure looks like this:
     *
     * [
     *   [ 'name': 'foo.jpg', ... ],
     *   [ 'name': 'bar.jpg', ... ],
     * ]
     *
     * ... rather than this:
     *
     * [
     *   'name': [
     *     'foo.jpg',
     *     'bar.jpg',
     *   ], ...
     * ]
     *
     * @param string $key
     *
     * @return array[]|false
     */
    public function getUploads($key) {
        $files = array();

        if (!isset($this->files[$key]) || !is_array($this->files[$key])) {
            return false;
        }

        foreach ($this->files[$key] as $attribute => $list) {
            foreach ($list as $key => $value) {
                $files[$key][$attribute] = $value;
            }
        }

        foreach ($files as $key => $file) {
            if ($file['name'] === '') {
                // remove empty files
                unset($files[$key]);
            } else {
                // validate upload
                $this->validateUpload($file);
            }
        }

        // re-index
        return array_values($files);
    }

    /**
     * @todo Remove register_global checks when 5.3 is dead.
     */
	protected function validateUpload($file) {
		if (!isset($file['error'])) {
            // tampering through register_globals detected
			throw new RequestException('Abnormal POST.');
        }

		switch ($file['error']) {
		case UPLOAD_ERR_OK:
			// The upload is seemingly okay - now let's be sure the file
            // actually originated from an upload and not through tampering with
            // register_globals.
			if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']))
            {
                // it's okay
                return;
            }

            $msg = 'Abnormal POST.';
            break;
		case UPLOAD_ERR_FORM_SIZE:
		case UPLOAD_ERR_INI_SIZE:
			$msg = 'The file is too large.';
			break;
		case UPLOAD_ERR_PARTIAL:
			$msg = 'The file was only partially uploaded.';
			break;
		case UPLOAD_ERR_NO_FILE:
			$msg = 'No file was uploaded.';
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$msg = 'Missing a temporary folder.';
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$msg = 'Failed to write file to disk.';
			break;
		default:
			$msg = 'Unable to save the uploaded file.';
		}

		throw new RequestException($msg);
	}

    /**
     * @todo remove this shit when PHP 5.6 comes out and/or 5.3 is abandoned
     */
    protected function unescapeMagicQuotes() {
        $process = array(&$this->get, &$this->post, &$this->cookie);

        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);

                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
