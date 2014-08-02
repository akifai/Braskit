<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Controller;

use Braskit\Controller;

// todo
use Braskit\Error_CSRF;
use Router_Main;
use Parser;

/**
 * Controller for board.php
 */
class Web extends Controller {
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
        return new Router_Main($this->app['url']->get());
    }

    public function exceptionHandler(\Exception $e) {
        $template = 'error.html';

        // used for return link
        $referrer = $this->app['request']->referrer;

        $message = $e->getMessage();

        if (!($e instanceof \HTMLException)) {
            // escape HTML
            $message = Parser::escape($message);
        }

        if ($e instanceof \BanException) {
            // show the ban screen
            $template = 'banned.html';
        }

        if (!($e instanceof Error_CSRF)) {
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
        } catch (\Exception $e) {
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

/* vim: set ts=4 sw=4 sts=4 et: */
