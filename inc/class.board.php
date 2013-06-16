<?php
defined('TINYIB') or exit;

class Board {
	public $title, $minlevel = 0, $config;
	private $exists, $board;

	public function __construct($board, $must_exist = true, $load_config = true) {
		// Check for blank board name
		if (!strlen($board))
			throw new Exception('Board name cannot be blank.');

		// Check for invalid characters
		if (!preg_match('/^[A-Za-z0-9]+$/', $board))
			throw new Exception('Board name contains invalid characters.');

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
		// TODO: this should look up the board in a table rather than
		// checking a table with the desired name exists

		if (is_bool($this->exists))
			return $this->exists;

		$vars = getBoard($this->board);

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
		if (!is_string($longname))
			throw new Exception("Missing board name.");

		if ($this->exists())
			throw new Exception('A board with that name already exists.');

		if ($check_folder && file_exists($this->board))
			throw new Exception('Folder name collision - refusing to create board.');

		// create tables/entries for board
		createBoard($this->board, $longname);

		// create folders
		foreach (array('', '/res', '/src', '/thumb') as $folder) {
			$folder = $this->board.$folder;

			if (!@mkdir(TINYIB_ROOT."/$folder"))
				throw new Exception("Couldn't create folder: {$folder}");
		}

		$this->exists = true;
	}

	/**
	 * Deletes a board.
	 * Use this with extreme caution.
	 * @todo: finish this
	 */
	public function destroy() {
		if (!$this->exists())
			throw new Exception("The board doesn't exist.");

		return deleteBoardTable($this->board);
	}

	/**
	 * Changes the title and level
	 */
	public function editSettings($title, $minlevel) {
		if (!length($title))
			throw new Exception("Invalid board title.");

		$min_level = abs($minlevel);

		if ($min_level > 0xffff)
			throw new Exception("Invalid user level.");

		$this->title = $title;
		$this->minlevel = $minlevel;

		return updateBoard($this->board, $title, $minlevel);
	}

	/**
	 * Inserts a post
	 */
	public function insert($post) {
		return insertPost($this->board, $post);
	}

	/**
	 * Deletes a post
	 */
	public function delete($id) {
		// make the parent post last
		$posts = array_reverse($this->postsInThread($id)); 

		foreach ($posts as $post) {
			// delete files belonging to the post
			deletePostImages($this->board, $post);

			// delete the post
			deletePostByID($this->board, $post);
		}
	}

	public function getAllThreads() {
		return allThreads($this->board);
	}

	// FIXME: This shit works by accident, not by design
	// A huge cleanup is needed.
	public function getIndexThreads($offset = false) {
		// get all threads
		if ($offset !== false) {
			$all_threads = getThreads(
				$this->board,
				$offset,
				$this->config->threads_per_page
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
				$replies = latestRepliesInThreadByID(
					$this->board,
					$thread[0]['id'],
					$replies_shown
				);

				foreach ($replies as $reply)
					$thread[] = $reply;
			} else {
				// nothing to fetch
				$replies = array();
			}

			// set the omission flag for the OP
			$thread[0]['omitted'] = ($replies_shown === count($replies))
				? $this->countPostsInThread($thread[0]['id'])
					- ($replies_shown + 1)
				: $thread[0]['omitted'] = 0;

			$threads[] = $thread;
		}

		return $threads;
	}

	public function countThreads() {
		return countThreads($this->board);
	}

	public function countPostsInThread($id) {
		return countPostsInThread($this->board, $id);
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
	 * Clear old threads
	 */
	public function trim() {
		$threads = getOldThreads($this->board, $this->config->max_threads);

		foreach ($threads as $thread)
			$this->delete($thread);

		return count($threads);
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

	public function postsInThread($id) {
		return postsInThreadByID($this->board, $id);
	}

	/**
	 * Rebuilds a thread cache
	 */
	public function rebuildThread($id) {
		$posts = $this->postsInThread($id);

		$html = $this->render('thread.html', array(
			'board' => $this,
			'posts' => $posts,
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
			$this->rebuildThread($thread['id']);
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

	public function checkFlood($time, $ip, $comment_hex, $has_file) {
		$iplib = new IP($ip);
		$ip = (string)$iplib->toInteger();

		// check if images are being posted too fast
		if ($has_file && $this->config->seconds_between_images > 0) {
			$max = $time - $this->config->seconds_between_images;

			if (checkImageFlood($ip, $max))
				throw new Exception('Flood detected.');

			return;
		}

		// check if text posts are being posted too fast
		if ($this->config->seconds_between_posts > 0) {
			$max = $time - $this->config->seconds_between_posts;

			if (checkFlood($ip, $max))
				throw new Exception('Flood detected.');
		}

		// check for duplicate text
		if ($comment_hex && !$this->config->allow_duplicate_text) {
			$max = $time - $this->config->seconds_between_duplicate_text;

			if (checkDuplicateText($comment_hex, $max))
				throw new Exception('Duplicate comment detected.');
		}
	}

	public function checkDuplicateImage($hex) {
		$row = postByHex($this->board, $hex);

		if ($row === false)
			return;

		$message = 'Error: The file <a href="%s">has been uploaded</a> previously.';
		$link = $this->linkToPost($row);

		throw new HTMLException(sprintf($message, $link));
	}

	public function handleUpload($name) {
		global $temp_dir;

		if (!isset($_FILES[$name]) || $_FILES[$name]['name'] === '')
			return false; // no file uploaded - nothing to do

		// Check for file[] or variable tampering through register_globals
		if (is_array($_FILES[$name]['name']))
			throw new Exception('Abnormal post.');

		// Check for uploading errors
		validateFileUpload($_FILES[$name]);

		extract($_FILES[$name], EXTR_REFS);

		// load image functions
		require 'inc/image.php';

		// Check file size
		if (($this->config->max_kb > 0) && ($size > ($this->config->max_kb * 1024)))
			throw new Exception(sprintf('That file is larger than %s KB.', $this->config->max_kb));

		// set some values
		$file['tmp'] = $tmp_name;
		$file['md5'] = md5_file($tmp_name);
		$file['size'] = $size;
		$file['origname'] = $name;

		// check for duplicate upload
		// TODO - Board shit
		$this->checkDuplicateImage($file['md5']);

		// generate a number to use as our filename
		$basename = time().substr(microtime(), 2, 3);

		$info = analyse_image($tmp_name);

		if ($info === false)
			throw new Exception('Only GIF, JPG, and PNG files are allowed.'); 

		$file['width'] = $info['width'];
		$file['height'] = $info['height'];

		// filename for main file
		$file['file'] = sprintf('%s.%s', $basename, $info['ext']);

		// filename for thumbnail
		$file['thumb'] = sprintf('%ss.%s', $basename, $info['ext']);

		// paths
		$file['location'] = $this->board.'/src/'.$file['file'];
		$file['t_location'] = $this->board.'/thumb/'.$file['thumb'];

		// make thumbnail sizes
		$t_size = make_thumb_size(
			$info['width'],
			$info['height'],
			$this->config->max_thumb_w,
			$this->config->max_thumb_h
		);

		if ($t_size === false) {
			// TODO: It may be desirable to thumbnail the image even if it's
			// small enough already.
			$file['t_tmp'] = true;

			$file['t_width'] = $info['width'];
			$file['t_height'] = $info['height'];
		} else {
			list($t_width, $t_height) = $t_size;

			// create a temporary name for thumbnail
			$file['t_tmp'] = tempnam($temp_dir, 'tinyib');

			// tempnam sets the wrong file permissions
			chmod($file['t_tmp'], 0664);

			// create thumbnail
			$created = createThumbnail($tmp_name, $file['t_tmp'],
				$info['ext'], $info['width'], $info['height'],
				$t_width, $t_height);

			if ($created) {
				// success
				$file['t_width'] = $t_width;
				$file['t_height'] = $t_height;
			} else {
				// we couldn't create the thumbnail for whatever reason
				// 0x0 indicates failure
				$file['t_width'] = 0;
				$file['t_height'] = 0;

				// indicate that we shouldn't bother with this further
				$file['t_tmp'] = false;
			}
		}

		return $file;
	}

	/**
	 * Board-specific Twig instance
	 */
	public function getTwig() {
		$dir = $this->board.'/templates';

		if (is_dir($dir))
			return load_twig(array($dir));

		return load_twig();
	}

	public function render($template, $args = array()) {
		if (!isset($this->twig))
			$this->twig = $this->getTwig();

		return render($template, $args, $this->twig);
	}

	/**
	 * Gets a post
	 */
	public function getPost($id) {
		return PostByID($this->board, $id);
	}

	/**
	 * Bumps a thread
	 */
	public function bump($id) {
		return BumpThreadByID($this->board, $id);
	}

	/**
	 * Returns a board-specific file path
	 */
	public function path($file, $internal = false) {
		$path = $this->board.'/'.$file;

		return expand_path($path, $internal);
	}

	/**
	 * Returns a link to a specific post
	 */
	public function linkToPost($row, $quote = false, $admin = false) {
		$link = sprintf('res/%d.html#%s%d',
			$row['parent'] ?: $row['id'],
			$quote ? 'i' : '',
			$row['id']
		);

		return $this->path($link, $admin);
	}

	/**
	 * Callback for formatting a post
	 * Use like this: array($board, 'formatPostRef')
	 */
	public function formatPostRef($match) {
		$row = postByID($this->board, $match[1]);

		if ($row === false)
			return $match[0]; // post does not exist

		$thread = $row['parent'] ? $row['parent'] : $row['id'];

		// this doesn't really belong in this class, but whatever
		$url = $this->path(sprintf('res/%d.html#%d', $thread, $row['id']));

		$html = sprintf('<a href="%s" class="postref">&gt;&gt;%s</a>',
			$url, $row['id']);

		return $html;
	}

	public function checkSpam($ip, $values) {
		global $spam_files;

		$spam = new Spam($spam_files);
		$spam->do_autoban = $this->config->enable_autobans;

		if (!$spam->arrayMatches($values))
			return;

		// this could be more elegant
		if (!$spam->no_ban) {
			$ban = array();
			$ban['ip'] = $ip;

			$ban['reason'] = sprintf(
				$this->config->autoban_spam_message,
				$spam->word
			);

			$ban['expire'] = ($s = $this->config->autoban_seconds)
				? $_SERVER['REQUEST_TIME'] + $s
				: 0;

			insertBan($ban);
		}

		throw new Exception('Spam detected.');
	}
}
