<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Symfony\Component\HttpFoundation\Response;

abstract class View {
    /**
     * App instance
     */
    protected $app;

    /**
     * The response body.
     *
     * @var Response 
     */
    public $response;

    /**
     * Template vars
     *
     * @var array
     */
    protected $templateVars = array();

    public function __construct(App $app) {
        $this->app = $app;

        $request = $app['request'];
        $this->response = new Response();

        $verb = $request->getRealMethod() === 'POST' ? 'post' : 'get';
        $method = array($this, $verb);

        if (!is_callable($method)) {
            $this->methodNotAllowed();
        }

        $args = $app['router']->matches;

        // set the first argument to $app
        array_unshift($args, $app);

        $this->response = call_user_func_array($method, $args);
    }

    protected function csrfScreen() {
        return $this->render('csrf.html');
    }

    /**
     * @todo
     */
    private function methodNotAllowed() {
        header('HTTP/1.0 405 Method Not Allowed');
        throw new Error('Method not allowed.');
    }

    /**
     * Sets a template variable.
     *
     * @param $key string Variable name.
     * @param $value mixed A value to pass to the templates.
     */
    protected function setVar($key, $value) {
        $this->templateVars[$key] = $value;
    }

    /**
     * Redirect to any URL.
     */
    protected function redirect($url) {
        $this->response->setStatusCode(303);
        $this->response->headers->set('Location', $url);

        $htmlURL = htmlspecialchars($url, ENT_QUOTES);

        $this->response->setContent(
            '<html><body>'.
                "<a href=\"$htmlURL\">$htmlURL</a>".
            '</body></html>'
        );

        return $this->response;
    }

    /**
     * Redirect to another route.
     */
    protected function diverge($dest, array $args = []) {
        if (substr($dest, 0, 1) !== '/') {
            // missing slash
            $dest = "/$dest";
        }

        $url = $this->app['url']->create($dest, $args);

        return $this->redirect($url);
    }

    /**
     * Renders a template. Any similarly named variables passed to the
     * template in this method will override those set with $this->setVar().
     *
     * @param string $template Template filename.
     * @param array $args Template variables.
     *
     * @return Response
     */
    protected function render($tpl, $args = []) {
        $template = $this->app['template'];

        $params = array_merge($this->templateVars, $args);

        $this->response->setContent($template->render($tpl, $params));

        return $this->response;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
