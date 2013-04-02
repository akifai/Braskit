<?php
defined('TINYIB') or exit;

class Board {
	private $board, $exists;

	public function __construct($board, $must_exist = true) {
		// Check for blank board name
		if (!strlen($board))
			throw new Exception('Board name cannot be blank.');

		// Check for invalid characters
		if (!preg_match('/^[A-Za-z0-9]+$/', $board))
			throw new Exception('Board name contains invalid characters.');

		$this->board = (string)$board;

		if ($must_exist && !$this->exists())
			throw new Exception("The board doesn't exist.");
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

		$this->exists = boardExists($this->board);

		return $this->exists;
	}

	/**
	 * Creates a board.
	 */
	public function create($longname) {
		if ($this->exists())
			throw new Exception('A board with that name already exists.');

		if (file_exists($this->board))
			throw new Exception('Folder name collision - refusing to create board.');

		// create folders
		foreach (array('', '/res', '/src', '/thumb') as $folder) {
			$folder = $this->board.$folder;

			if (!@mkdir(TINYIB_ROOT."/$folder"))
				throw new Exception("Couldn't create folder: {$folder}");
		}

		// create table for board
		createBoardTables($this->board);
		createBoardEntry($this->board, $longname);

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
		$posts = array_reverse(postsInThreadByID($this->board, $id)); 

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

	public function getIndexThreads($offset = false) {
		if ($offset !== false)
			$all_threads = getThreads($this->board, $offset);
		else
			$all_threads = $this->getAllThreads(); 

		$threads = array();

		// TODO: This is ugly trevor code. Replace it.
		foreach ($all_threads as $thread) {
			$thread = array($thread);
			$replies = latestRepliesInThreadByID($this->board, $thread[0]['id']);

			foreach ($replies as $reply)
				$thread[] = $reply;

			$thread[0]['omitted'] = (count($replies) == 3)
				? (count(postsInThreadByID($this->board, $thread[0]['id'])) - 4)
				: 0;

			$threads[] = $thread;
		}

		return $threads;
	}

	public function countThreads() {
		return countThreads($this->board);
	}

	/**
	 * Clear old threads
	 */
	public function trim() {
		return trimThreads($this->board);
	}

	/**
	 * Rebuild index caches
	 */
	public function rebuildIndexes() {
		$threads = $this->getIndexThreads();
		$maxpage = get_page_count(count($threads)) - 1;

		$num = 0;

		$page = array_splice($threads, 0, 10);
		do {
			$file = !$num ? 'index.html' : $num.'.html';
			$html = render('page.html', array(
				'board' => $this->board,
				'maxpage' => $maxpage,
				'threads' => $page,
				'pagenum' => $num,
			));

			$this->write($file, $html);
			$num++;
		} while ($page = array_splice($threads, 0, 10));

		// delete old caches
		while ($this->fileExists($num.'.html')) {
			$this->unlink($num.'.html');
			$num++;
		}
	}

	/**
	 * Rebuilds a thread cache
	 */
	public function rebuildThread($id) {
		$posts = postsInThreadByID($this->board, $id);
		$html = render('thread.html', array(
			'board' => $this->board,
			'posts' => $posts,
			'thread' => $id,
		));

		$this->write(sprintf('res/%d.html', $id), $html);
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

	public function checkDuplicateImage($hex) {
		$row = postByHex($this->board, $hex);
		if ($row === false)
			return;

		// TODO: link to file
		throw new Exception('Duplicate file uploaded.');
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
		if ((TINYIB_MAXKB > 0) && ($size > (TINYIB_MAXKB * 1024)))
			throw new Exception(sprintf('That file is larger than %s.', TINYIB_MAXKBDESC));

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
			TINYIB_MAXW,
			TINYIB_MAXH
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
	public function path($file) {
		return expand_path($this->board.'/'.$file);
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
}
