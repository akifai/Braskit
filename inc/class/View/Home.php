<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Home extends View {
	protected function get() {
		diverge('/login');
	}
}
