<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Controller;

use Braskit\Controller;
use Braskit\Error;
use Braskit\Error\CSRF as CSRFError;
use Braskit\Parser;
use Braskit\Router\Main as Router;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for board.php
 */
class Web extends Controller {
    const CONTENT_TYPE = 'Content-Type: text/html; charset=UTF-8';
    const INSERT_STR = '<!--footer_insert-->';

    public function run() {
        $hasConfig = $this->app->loadConfig();

        if (!$hasConfig) {
            return new Response(
                '<html><body><a href="install.php">Click me</a></body></html>',
                303,
                ['Location' => 'install.php']
            );
        }

        return $this->app['view']->response;
    }

    public function getRouter() {
        return new Router($this->app['url']->get());
    }

    public function exceptionHandler(\Exception $e) {
        $response = new Response();

        $template = 'error.html';

        // used for return link
        $referrer = $this->app['request']->headers->get('Referer');

        $message = $e->getMessage();

        if ($e instanceof Error) {
            $html = $e->getHTMLMessage();

            if ($html === null) {
                // no HTML message - escape the regular one
                $message = Parser::escape($message);
            } else {
                $message = $html;
            }
        }

        if ($e instanceof \BanException) {
            // show the ban screen
            $template = 'banned.html';
        }

        if (!($e instanceof CSRFError)) {
            // prevent CSRF errors on resubmission
            $this->app['csrf']->rollback();
        }

        try {
            // Error messages using Twig
            $response->setContent($this->app['template']->render($template, [
                'exception' => $e,
                'message' => $message,
                'referrer' => $referrer,
            ]));
        } catch (\Exception $e) {
            // Twig failed, rip
            $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

            $response->setContent(
                "[Braskit] Fatal exception/template error.\n\n".
                $e->getMessage()
            );
        }

        return $response;
    }

    /**
     * Output buffer handler that inserts the page generation time.
     *
     * @todo Turn into middleware.
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

        $request_time = $app['request']->server->get('REQUEST_TIME_FLOAT');
        $total_time = microtime(true) - $request_time;

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
