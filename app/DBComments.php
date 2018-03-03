<?php

class DBComments {
	const COMMENT_TYPES = [
		'KEYWORD',
		'UNIFICATION',
		'UNIFICATION_LOOSE',
		'UNIFICATION_COMPLEX',
		'ATTRIBUTES_RADICAL',
		'ATTRIBUTES_FS',
		'ATTRIBUTES_SC',
		'ATTRIBUTES_TC',
		'ATTRIBUTES_IDS',
		'ATTRIBUTES_TRAD_SIMP',
		'MISDESIGNED_GLYPH',
		'NORMALIZATION',
		'COMMENT',
		'DISUNIFICATION',
		'UNCLEAR_EVIDENCE',
		'EDITORIAL_ERROR',
		'SEMANTIC_VARIANT',
		'SIMP_VARIANT',
		'TRAD_VARIANT',
		'CODEPOINT_CHANGED',
		'OTHER'
	];

	public $db;
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	public function getSN() {
		return sprintf('%05d', $this->sn);
	}

	public function getTypeIndex() {
		return array_search($this->type, self::COMMENT_TYPES);
	}

	public static function getList($filter = false) {
		$q = Env::$db->query('SELECT * FROM "comments" WHERE "version" = 5');
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getList2($filter = false) {
		$q = Env::$db->query('SELECT * FROM "comments" WHERE "version" = 4 AND ("type" LIKE \'NORMALIZATION%\' AND "type" NOT LIKE \'%RADICAL\')');
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function find($type, $content) {
		$q = Env::$db->prepare('SELECT COUNT(*) FROM "comments" WHERE "version" = 5 AND "type" = ? AND "comment" LIKE ?');
		$q->execute([$type, '%'.trim($content).'%']);
		return (bool) $q->fetchColumn();
	}

	public static function getAll($sq_number) {
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "sn" = ? ORDER BY "version" DESC');
		$q->execute([$sq_number]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getByKeyword($keyword) {
		$q = Env::$db->prepare('SELECT "sn" FROM "comments" WHERE "type" = ? AND "comment" = ? ORDER BY "version" DESC');
		$q->execute(['KEYWORD', $keyword]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = str_pad(intval(ltrim($data->sn, '0')), 5, '0', STR_PAD_LEFT);
		}
		return $results;
	}

	public static function getAllKeywords() {
		$q = Env::$db->prepare('SELECT DISTINCT "comment" as "keyword" FROM "comments" WHERE "type" = ?');
		$q->execute(['KEYWORD']);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = $data->keyword;
		}
		return $results;
	}

	public static function save($sq_number, $type, $comment) {
		$q = Env::$db->prepare('INSERT INTO "comments" ("sn", "type", "comment", "version") VALUES (?, ?, ?, ?)');
		$q->execute([$sq_number, $type, $comment, Workbook::VERSION]);
	}
}
