<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * @todo This thing is a huge pile of shit and needs to be redone and
 *       reorganised. In particular, many methods here would do better in either
 *       the Post or File classes. Quite a bit of Trevor code still remains
 *       here; it should be rewritten.
 */
class Board {
	const BOARD_RE = '[A-Za-z0-9]+';

	public $config;

	public $title;
	public $minlevel = 0;

	protected $exists;
	protected $board;

	protected $twig;

	public static function validateName($board) {
		// Check for blank board name
		if (!strlen($board))
			throw new Exception('Board name cannot be blank.');

		// Check for invalid characters
		if (!preg_match('/^'.self::BOARD_RE.'$/', $board))
			throw new Exception('Board name contains invalid characters.');
	}

	public function __construct($board, $must_exist = true, $load_config = true) {
		$this->validateName($board);

		$this->board = (string)$board;

		if ($must_exist) {
			if (!$this->exists())
				throw new Exception("The board doesn't exist.");

			if ($load_config)
				$this->config = new BoardConfig($this);
		}
	}

	public function __toString() {
		return $this->board;
	}

	/**
	 * Checks if the board exists
	 */
	public function exists() {
		global $app;

		// TODO: this should look up the board in a table rather than
		// checking a table with the desired name exists

		if (is_bool($this->exists))
			return $this->exists;

		$vars = $app['db']->getBoard($this->board);

		if ($vars) {
			$this->exists = true;
			$this->title = $vars['longname'];
			$this->minlevel = $vars['minlevel'];

			return true;
		}

		$this->exists = false;

		return false;
	}

	/**
	 * Creates a board.
	 */
	public function create($longname, $check_folder = true) {
		global $app;

		if (!is_string($longname))
			throw new Exception("Missing board name.");

		if ($this->exists())
			throw new Exception('A board with that name already exists.');

		if ($check_folder && file_exists($this->board))
			throw new Exception('Folder name collision - refusing to create board.');

		// create tables/entries for board
		$app['db']->createBoard($this->board, $longname);

		// create folders
		foreach (array('', '/res', '/src', '/thumb') as $folder) {
			$folder = $this->board.$folder;

			if ($check_folder && !@mkdir(TINYIB_ROOT."/$folder"))
				throw new Exception("Couldn't create folder: {$folder}");
		}

		$this->exists = true;
	}

	public function rename($newname) {
		global $app;

		$this->validateName($newname);

		$app['dbh']->beginTransaction();

		// rename the board in SQL
		try {
			$app['db']->renameBoard($this->board, $newname);
		} catch (PDOException $e) {
			$err = $e->getCode();

			switch ($err) {
			case UNIQUE_VIOLATION:
				// board exists
				throw new Exception("The board '$board' already exists!");
			default:
				// unknown error
				throw $e;
			}
		}

		$oldfolder = TINYIB_ROOT.'/'.$this->board;
		$newfolder = TINYIB_ROOT.'/'.$newname;

		if (file_exists($newfolder))
			throw new Exception('Folder name collision - cannot rename board.');

		$renamed = @rename($oldfolder, $newfolder);

		if (!$renamed)
			throw new Exception("Write error - cannot rename the board.");

		$app['dbh']->commit();

		$this->board = (string)$newname;
	}

	/**
	 * Deletes a board.
	 * Use this with extreme caution.
	 * @todo: finish this
	 */
	public function destroy() {
		global $app;

		if (!$this->exists())
			throw new Exception("The board doesn't exist.");

		return $app['db']->deleteBoardTable($this->board);
	}

	/**
	 * Changes the title and level
	 */
	public function editSettings($title, $minlevel) {
		global $app;

		if (!length($title))
			throw new Exception("Invalid board title.");

		$min_level = abs($minlevel);

		if ($min_level > 0xffff)
			throw new Exception("Invalid user level.");

		$this->title = $title;
		$this->minlevel = $minlevel;

		return $app['db']->updateBoard($this->board, $title, $minlevel);
	}

	/**
	 * Inserts a post
	 */
	public function insert($post) {
		global $app;

		return $app['db']->insertPost($post);
	}

	/**
	 * Deletes a post
	 */
	public function delete($id, $password = null) {
		global $app;

		$posts = $app['db']->deletePostByID($this->board, $id, $password);

		foreach ($posts as $post) {
			$this->deletePostFiles($post);
		}
	}


	/**
	 * Deletes the files associated with a post.
	 */
	public function deletePostFiles(FileMetaData $file) {
		$files = array();

		if ($file->file)
			$files[] = "$this/src/{$file->file}";

		if ($file->thumb)
			$files[] = "$this/thumb/{$file->thumb}";

		if ($file instanceof Post && !$file->parent)
			$files[] = "$this/res/{$file->id}.html";

		foreach ($files as $file) {
			@unlink(TINYIB_ROOT.'/'.$file);
		}
	}

	public function report($posts, $ip, $reason) {
		global $app;

		return $app['db']->insertReports($posts, array(
			'board' => $this->board,
			'ip' => $ip,
			'time' => time(),
			'reason' => $reason,
		));
	}

	public function getAllThreads() {
		global $app;

		return $app['db']->allThreads($this->board);
	}

	// FIXME: This shit works by accident, not by design
	// A huge cleanup is needed.
	public function getIndexThreads($offset = false, $admin = false) {
		global $app;

		// get all threads
		if ($offset !== false) {
			$all_threads = $app['db']->getThreads(
				$this->board,
				$offset,
				$this->config->threads_per_page,
				$admin
			);
		} else {
			$all_threads = $this->getAllThreads();
		}

		$threads = array();

		// to avoid having to write $this->config... every time (plus
		// there's a slight overhead when fetching dynamic properties)
		$replies_shown = $this->config->replies_shown;

		foreach ($all_threads as $thread) {
			// every thread is an array where the first element
			// is the OP
			$thread = array($thread);

			// fetch the latest posts and append them to the thread
			if ($replies_shown) {
				$replies = $app['db']->latestRepliesInThreadByID(
					$this->board,
					$thread[0]->id,
					$replies_shown,
					$admin
				);

				foreach ($replies as $reply)
					$thread[] = $reply;
			} else {
				// nothing to fetch
				$replies = array();
			}

			// set the omission flag for the OP
			$thread[0]->omitted = ($replies_shown === count($replies))
				? $this->countPostsInThread($thread[0]->id)
					- ($replies_shown + 1)
				: $thread[0]->omitted = 0;

			$threads[] = $thread;
		}

		return $threads;
	}

	public function countThreads() {
		global $app;

		return $app['db']->countThreads($this->board);
	}

	public function countPostsInThread($id) {
		global $app;

		return $app['db']->countPostsInThread($this->board, $id);
	}

	/**
	 * Get the highest page number, starting from 0
	 */
	public function getMaxPage($arg) {
		$count = is_array($arg) ? count($arg) : (int)$arg;
		$total = $this->config->threads_per_page;

		if (!$count || !$total)
			return 0;

		return floor(($count + $total - 1) / $total) - 1;
	}

	/**
	 * Clear old posts and files.
	 */
	public function trim() {
		global $app;

		// remove posts
		$posts = $app['db']->trimPostsByThreadCount(
			$this->board,
			$this->config->max_threads
		);

		// delete the files associated with every post
		foreach ($posts as $post) {
			$this->deletePostFiles($post);
		}

		return count($posts);
	}

	/**
	 * Rebuild all indexes and threads
	 */
	public function rebuildAll() {
		$this->rebuildIndexes();
		$this->rebuildThreads();
	}

	/**
	 * Rebuild index caches
	 */
	public function rebuildIndexes() {
		$threads = $this->getIndexThreads();
		$maxpage = $this->getMaxPage($threads);
		$threads_per_page = $this->config->threads_per_page;

		$num = 0;

		$page = array_splice($threads, 0, $threads_per_page);
		do {
			$file = !$num ? 'index.html' : $num.'.html';

			$html = $this->render('page.html', array(
				'board' => $this,
				'maxpage' => $maxpage,
				'static' => true,
				'threads' => $page,
				'pagenum' => $num,
			));

			$this->write($file, $html);
			$num++;
		} while ($page = array_splice($threads, 0, $threads_per_page));

		// delete old caches
		while ($this->fileExists($num.'.html')) {
			$this->unlink($num.'.html');
			$num++;
		}
	}

	public function postsInThread($id, $admin = false) {
		global $app;

		return $app['db']->postsInThreadByID($this->board, $id, $admin);
	}

	/**
	 * Rebuilds a thread cache
	 */
	public function rebuildThread($id) {
		$posts = $this->postsInThread($id);

		$html = $this->render('thread.html', array(
			'board' => $this,
			'posts' => $posts,
			'static' => true,
			'thread' => $id,
		));

		$this->write(sprintf('res/%d.html', $id), $html);
	}

	/**
	 * Rebuild all threads
	 */
	public function rebuildThreads() {
		$threads = $this->getAllThreads();

		foreach ($threads as $thread)
			$this->rebuildThread($thread->id);
	}

	/**
	 * Write a file to a board directory
	 */
	public function write($filename, $contents) {
		$filename = sprintf('%s/%s', $this->board, $filename);
		return writePage($filename, $contents);
	}

	public function unlink($filename) {
		return unlink($this->board.'/'.$filename);
	}

	public function fileExists($filename) {
		return file_exists($this->board.'/'.$filename);
	}

	public function checkFlood($time, $ip, $comment, $has_file) {
		global $app;

		// check if images are being posted too fast
		if ($has_file && $this->config->seconds_between_images > 0) {
			$max = $time - $this->config->seconds_between_images;

			if ($app['db']->checkImageFlood($ip, $max)) {
				throw new Exception('Flood detected.');
			}

			// duplicate text allowed on images
			return;
		}

		// check if text posts are being posted too fast
		if ($this->config->seconds_between_posts > 0) {
			$max = $time - $this->config->seconds_between_posts;

			if ($app['db']->checkFlood($ip, $max)) {
				throw new Exception('Flood detected.');
			}
		}

		// check for duplicate text
		if ($comment && !$this->config->allow_duplicate_text) {
			$max = $time - $this->config->seconds_between_duplicate_text;

			if ($app['db']->checkDuplicateText($comment, $max)) {
				throw new Exception('Duplicate comment detected.');
			}
		}
	}

	public function checkDuplicateImage($hex) {
		global $app;

		$row = $app['db']->postByMD5($this->board, $hex);

		if ($row === false)
			return;

		$message = 'Error: The file <a href="%s">has been uploaded</a> previously.';
		$link = $this->linkToPost($row);

		throw new HTMLException(sprintf($message, $link));
	}

	public function handleUpload($upload) {
		$root = TINYIB_ROOT.'/'.$this->board;

		$file = new File($upload, "$root/src");

		if (!$file->exists) {
			return $file;
		}

		// because the whole thing is too long to type, and because
		// dynamic properties have a lot of overhead...
		$max_kb = $this->config->max_kb;

		// Check file size
		if ($max_kb > 0 && $file->size > $max_kb * 1024) {
			throw new Exception("The file cannot be larger than $max_kb KB.");
		}

		// check for duplicate upload
		$this->checkDuplicateImage($file->md5);

		// create thumbnail
		$file->thumb("$root/thumb",
			$this->config->max_thumb_w,
			$this->config->max_thumb_h
		);

		return $file;
	}

	protected function getTwig() {
		global $app;

		if (isset($this->twig)) {
			return $this->twig;
		}

		$path = TINYIB_ROOT."/$this->board/templates";

		if (!is_dir($path) || !is_readable($path)) {
			// no template directory and/or bad permissions, so just
			// use the global twig object
			$this->twig = $app['template'];
		} else {
			$chain = $app['template.chain'];

			$chain->addLoader(new PlainIB_Twig_Loader($path));
			$chain->addLoader($app['template.loader']);

			$this->twig = $app['template.creator']($chain);
		}

		return $this->twig;
	}

	public function render($template, $args = array()) {
		$twig = $this->getTwig();

		return $twig->render($template, $args);
	}

	/**
	 * Gets a post
	 */
	public function getPost($id) {
		global $app;

		return $app['db']->postByID($this->board, $id);
	}

	/**
	 * Bumps a thread
	 */
	public function bump($id) {
		global $app;

		return $app['db']->BumpThreadByID($this->board, $id);
	}

	/**
	 * Returns a board-specific file path
	 */
	public function path($file, $internal = false) {
		$path = $this->board.'/'.$file;

		return expand_path($path, $internal);
	}

	public function apiPath($dest, $vars) {
		$path = $this->board.'/'.$dest;

		return expand_script_path("ajax.php", $path, $vars);
	}

	/**
	 * Returns a link to a specific post
	 */
	public function linkToPost(Post $post, $quote = false, $admin = false) {
		$link = sprintf('res/%d.html#%s%d',
			$post->parent ?: $post->id,
			$quote ? 'i' : '',
			$post->id
		);

		return $this->path($link, $admin);
	}

	/**
	 * Parser modifier for linkifying citations.
	 */
	public function linkifyCitations(&$text) {
		$callable = array($this, 'formatPostRef');

		$text = preg_replace_callback(
			// $1: &gt;/BOARD_NAME/
			// $2: BOARD_NAME
			// $3: post id
			'@&gt;&gt;(&gt;/('.self::BOARD_RE.')/)?(\d+)@',
			$callable,
			$text
		);
	}

	protected function formatPostRef($matches) {
		if (isset($matches[2]) && $matches[2] !== '') {
			// >>>/board/123
			try {
				$board = new Board($matches[2], true, false);
			} catch (Exception $e) {
				// board doesn't exist or something - ignore it
				return $matches[0];
			}
		} elseif ($matches[3] > 0) {
			// >>123
			$board = $this;
		}

		$post = $board->getPost($matches[3]);

		if ($post === false) {
			// post does not exist
			return $matches[0];
		}

		$url = $board->linkToPost($post);

		return sprintf(
			'<a href="%s" class="postref">&gt;&gt;%s%s</a>',
			Parser::escape($url),
			$matches[1],
			$post->id
		);
	}

	public function checkSpam($ip, $values) {
		global $spam_files;

		$spam = new Spam($spam_files);
		$spam->do_autoban = $this->config->enable_autobans;

		if (!$spam->arrayMatches($values))
			return;

		if (!$spam->no_ban) {
			$ban = new BanCreate($ip);
			$ban->setReason(sprintf(
				$this->config->autoban_spam_message,
				$spam->word
			));
			$ban->setExpire($this->config->autoban_seconds);

			$ban->add();
		}

		throw new Exception('Spam detected.');
	}
}
