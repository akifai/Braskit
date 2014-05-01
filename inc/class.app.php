<?php

class App extends Pimple {
	/**
	 * @deprecated
	 */
	public function __toString() {
		return $this['path']->get();
	}
}
