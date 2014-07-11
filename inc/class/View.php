<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

abstract class View {
    /**
     * App instance
     */
    protected $app;

    /**
     * The response body.
     *
     * @var string
     * @todo
     */
    public $responseBody = '';

    /**
     * Template vars
     *
     * @var array
     */
    protected $templateVars = array();

    public function __construct(App $app) {
        $this->app = $app;

        $request = $app['request'];

        if (!$request->method) {
            throw new LogicException('View executed outside of HTTP context.');
        }

        $verb = $request->method === 'POST' ? 'post' : 'get';
        $method = array($this, $verb);

        if (!is_callable($method)) {
            $this->methodNotAllowed();
        }

        $args = $app['router']->matches;

        // set the first argument to $app
        array_unshift($args, $app);

        $this->responseBody = call_user_func_array($method, $args);
    }

    protected function csrfScreen() {
        return $this->render('csrf.html');
    }

    /**
     * @todo
     */
    private function methodNotAllowed() {
        header('HTTP/1.0 405 Method Not Allowed');
        throw new Exception('Method not allowed.');
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
     * Renders a template. Any similarly named variables passed to the
     * template in this method will override those set with $this->setVar().
     *
     * @param string $template Template filename.
     * @param array $args Template variables.
     *
     * @return string Rendered template.
     */
    protected function render($tpl, $args = array()) {
        $template = $this->app['template'];

        $params = array_merge($this->templateVars, $args);

        return $template->render($tpl, $params);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
