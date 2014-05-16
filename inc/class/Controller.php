<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

abstract class Controller {
    protected $app;

    public function __construct(App $app) {
        $this->app = $app;

        $self = $this;

        $app['router'] = function () use ($self) {
            return $self->getRouter();
        };
    }

    abstract public function run();

    /**
     * Exception handler.
     *
     * @param Exception $e
     * @return void
     */
    abstract public function exceptionHandler(Exception $e);

    /**
     * Get an instance of Router.
     *
     * @return Router
     */
    abstract protected function getRouter();

    /**
     * Do stuff that messes with PHP's global state.
     */
    protected function globalSetup() {
        // bad things could happen without this
        ignore_user_abort(true);

        // TODO
        define('TINYIB', null);
        define('TINYIB_ROOT', $this->app['path.root']);

        // TODO
        require($this->app['path.root'].'/inc/functions.php');

        date_default_timezone_set($this->app['timezone']);

        if (get_magic_quotes_gpc()) {
            // some functions will alter their output depending on what this is
            // set to. cleaning up GET/POST/COOKIE is done in the Request class.
            set_magic_quotes_runtime(false);
        }
    }
}

/**
 * Controller for board.php
 */
class Controller_Web extends Controller {
    const CONTENT_TYPE = 'Content-Type: text/html; charset=UTF-8';
    const INSERT_STR = '<!--footer_insert-->';

    public function run() {
        while (ob_get_level()) {
            ob_end_clean();
        }

        ob_start(array($this, 'obHandler'));

        $this->globalSetup();

        $hasConfig = $this->app->loadConfig();

        if (!$hasConfig) {
            // redirect to installer
            redirect('install.php');

            return;
        }

        header(self::CONTENT_TYPE);

        echo $this->app['view']->responseBody;

        ob_end_flush();
    }

    public function getRouter() {
        return new Router_Main($this->app['path']->get());
    }

    public function exceptionHandler(Exception $e) {
        $template = 'error.html';

        // used for return link
        $referrer = $this->app['request']->referrer;

        $message = $e->getMessage();

        if (!($e instanceof HTMLException)) {
            // escape HTML
            $message = Parser::escape($message);
        }

        if ($e instanceof BanException) {
            // show the ban screen
            $template = 'banned.html';
        }

        if (!($e instanceof CSRFException)) {
            // prevent CSRF errors on resubmission
            $this->app['csrf']->rollback();
        }

        try {
            // Error messages using Twig
            echo $this->app['template']->render($template, array(
                'exception' => $e,
                'message' => $message,
                'referrer' => $referrer,
            ));
        } catch (Exception $e) {
            // Twig failed, rip
            header('Content-Type: text/plain; charset=UTF-8');

            echo "[Braskit] Fatal exception/template error.\n\n";
            echo $e->getMessage();
        }
    }

    /**
     * Output buffer handler that inserts the page generation time.
     *
     * @return string
     */
    public function obHandler($buffer) {
        $app = $this->app;

        if (!in_array(self::CONTENT_TYPE, headers_list())) {
            // non-standard content-type - don't modify the output
            return $buffer;
        }

        // the part of the buffer to insert before
        $ins = strrpos($buffer, self::INSERT_STR);

        if ($ins === false) {
            // nowhere to insert the debug string
            return $buffer;
        }

        // first part of the new buffer
        $newbuf = substr($buffer, 0, $ins);

        $total_time = microtime(true) - $app['request']->microtime;

        // Append debug text
        $newbuf .= sprintf('<br>Page generated in %0.4f seconds.', $total_time);

        if ($app['counter']->dbQueries) {
            $query_time = round(100 / $total_time * $app['counter']->dbTime);

            $newbuf .= sprintf(' %d%% was spent running %d database queries.',
                $query_time, $app['counter']->dbQueries
            );
        }

        // the rest of the buffer
        $newbuf .= substr($buffer, $ins);

        return $newbuf;
    }
}

/**
 * Controller for ajax.php
 */
class Controller_Ajax extends Controller {
    const CONTENT_TYPE = 'Content-Type: application/json; charset=UTF-8';

    public function run() {
        // FIXME - this is needed because of the diverge() function
        global $ajax;

        while (ob_get_level()) {
            ob_end_clean();
        }

        // never let PHP print errors - this fucks up the JSON
        ini_set('display_errors', 0);

        $this->globalSetup();

        ob_start(array($this, 'obHandler'));

        define('TINYIB_BASE_TEMPLATE', 'ajax_base.html'); // TODO

        $hasConfig = $this->app->loadConfig();

        if (!$hasConfig) {
            // redirect to installer
            redirect('install.php');

            return;
        }

        $ajax = array(
            'error' => false,
        );

        header(self::CONTENT_TYPE);

        echo $this->app['view']->responseBody;

        ob_end_flush();
    }

    public function getRouter() {
        return new Router_Main($this->app['path']->get());
    }

    public function obHandler($buffer) {
        global $ajax;

        $ajax['page'] = $buffer;

        return json_encode($ajax);
    }

    public function exceptionHandler(Exception $e) {
        ob_end_clean();

        header('HTTP/1.1 403 Forbidden');

        echo json_encode(array(
            'error' => true,
            'errorMsg' => $e->getMessage(),
        ));
    }
}

/**
 * Controller for install.php
 */
class Controller_Install extends Controller {
    const CONTENT_TYPE = 'Content-Type: text/html; charset=UTF-8';

    public function run() {
        if (!ob_get_level()) {
            ob_start();
        }

        $app = $this->app;

        $this->globalSetup();

        $config = $this->app['path.root'].'/config.php';

        if (file_exists($config) && !isset($app['session']['installer'])) {
            header('HTTP/1.1 403 Forbidden');

            echo 'Braskit is already installed. ',
                'To re-run the installer, move or delete config.php.';

            exit;
        }

        // let us download the session-stored config without using cookies
        ini_set('session.use_only_cookies', false);

        $app['session']['installer'] = true;

        header(self::CONTENT_TYPE);

        echo $app['view']->responseBody;
    }

    public function getRouter() {
        return new Router_Install($this->app['path']->get());
    }

    public function exceptionHandler(Exception $e) {
        // too lazy to bother with this now
        var_dump($e);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
