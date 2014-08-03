<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Board; // todo
use Braskit\Error;
use Braskit\View;

class Report extends View {
    protected function get($app, $boardname) {
        $board = new Board($boardname);
        $config = $board->config;

        if (!$config->enable_reports)
            throw new Error('You cannot report posts on this board.');

        $posts = get_ids($board);

        if (!$posts) {
            redirect($board->path(''));

            return;
        }

        return $this->render('report.html', array(
            'board' => $board,
            'posts' => $posts,
        ));
    }

    protected function post($app, $boardname) {
        $app['csrf']->check();

        $board = new Board($boardname);
        $config = $board->config;

        if (!$config->enable_reports)
            throw new Error('You cannot report posts on this board.');

        // We don't want banned users reporting.
        Ban::check($app['request']->ip, time());

        // prevent flooding the reports
        if ($config->seconds_between_reports) {
            $threshold = time() - $config->seconds_between_reports;

            if ($app['db']->checkReportFlood($app['request']->ip, $threshold)) {
                throw new Error('You are reporting too fast!');
            }
        }

        $posts = get_ids($board);
        $reason = $app['param']->get('reason');

        $board->report($posts, $app['request']->ip, $reason);

        // TODO: Confirmation message
        redirect($board->path(''));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
