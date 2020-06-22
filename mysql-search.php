<?php

use voku\helper\UTF8;
use voku\helper\StopWords;
use Wamania\Snowball\StemmerFactory;

class Search {
	private $dbh = null; // The database handler.
	private $stopwords = [];
	private $stemmer = null;
	private $removeStopWords = false;
	private $doStemming = false;
	private $minWordLength = 3;

	// Search parameters:
	private $lang = null;
	private $pageNumber = 1;
	private $maxResultsPerPage = 10;
	private $tableName = null;
	private $fieldsToFetch = null;
	private $fieldsToMatch = null;
	private $extraSQL = '';
	private $extraParams = [];

	public function setDatabaseHandler($databaseHandler) {
		if ($databaseHandler instanceof PDO || $databaseHandler instanceof MySQLi) {
			$this->dbh = $databaseHandler;
		}
	}

	private function loadStopWords() {
		try {
			$this->stopwords = (new StopWords())->getStopWordsFromLanguage($this->lang);
		} catch (Exception $ex) {
		}
	}

	private function loadStemmer() {
		try {
			$this->stemmer = StemmerFactory::create($this->lang);
		} catch (Exception $ex) {
		}
	}

	// Enable or disable the option for removing stopwords
	public function setRemoveStopWords($value) {
		if (is_bool($value)) {
			$this->removeStopWords = $value;
		}
	}

	// Enable or disable the option for applying a stemming algorithm
	public function setDoStemming($value) {
		if (is_bool($value)) {
			$this->doStemming = $value;
		}
	}

	// Sets the minimmum length of word to start searching
	public function setMinWordLength($value) {
		if (is_int($value) && $value > 0) {
			$this->minWordLength = $value;
		}
	}

	// Sets the language of search.
	public function setLang($isoCode) {
		if (is_string($isoCode)) {
			if ($isoCode != $this->lang) {
				$this->stopwords = [];
				$this->stemmer = null;
			}
			$this->lang = $isoCode;
		}
	}

	// Sets page number for search
	public function setPageNumber($pageNumber) {
		$pageNumber = intval($pageNumber);
		if ($pageNumber > 0) {
			$this->pageNumber = $pageNumber;
		} else {
			$this->pageNumber = 1;
		}
	}

	// Sets the maximum number of results allowed per page
	public function setMaxResultsPerPage($maxResultsPerPage) {
		$maxResultsPerPage = intval($maxResultsPerPage);
		if ($maxResultsPerPage > 0) {
			$this->maxResultsPerPage = $maxResultsPerPage;
		} else {
			$this->maxResultsPerPage = 10;
		}
	}

	// Sets the table to use in search
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	// Sets the fields to use in search
	public function setFieldsToMatch(...$fieldsToMatch) {
		$this->fieldsToMatch = implode(', ', $fieldsToMatch);
	}

	// Sets the fields to return in search results
	public function setFieldsToFetch(...$fieldsToFetch) {
		$this->fieldsToFetch = implode(', ', $fieldsToFetch);
	}

	// Check if a word is not a stopword.
	private function isNotStopWord($word) {
		return !in_array($word, $this->stopwords);
	}

	// Remove stop words from an array of words
	private function removeStopWords($words) {
		return array_filter($words, [$this, 'isNotStopWord']);
	}

	private function isNotShortWord($word) {
		return UTF8::strlen($word) >= $this->minWordLength;
	}

	private function removeShortWords($words) {
		return array_filter($words, [$this, 'isNotShortWord']);
	}

	// Performs a stemming algorithm on given word
	private function getWordStem($word) {
		return $this->stemmer->stem($word);
	}

	// Given an array of words, returns an array of respective stems
	private function wordsToStems($words) {
		if (is_null($this->stemmer)) {
			return $words;
		}
		return array_map([$this, 'getWordStem'], $words);
	}

	// Split a string into an array respective unique words
	private function stringToWords($string) {
		return array_unique(UTF8::str_to_words($string, '', true, null));
	}

	// Add more parameters to sql where clause
	public function addParams($sql, ...$params) {
		$this->extraSQL = $sql;
		$this->extraParams = $params;
	}

	// Auxiliary method that performs a MySQL SELECT operation on database using PDO or MySQLi
	private function dbQuery($sql, $params, $fetchAll = false) {
		$stmt = $this->dbh->prepare($sql);
		if ($this->dbh instanceof PDO) {
			$stmt->execute($params);
			if ($fetchAll) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			}
		} elseif ($this->dbh instanceof MySQLi) {
			$stmt->bind_param(str_repeat('s', count($params)), ...$params);
			$stmt->execute();
			if ($fetchAll) {
				$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
			} else {
				$result = $stmt->get_result()->fetch_assoc();
			}
		}
		return $result;
	}

	// Method that performs a text search and returns results as an asociative array.
	// Call it after search parameters are setted.
	public function exec($terms) {
		$words = $this->stringToWords($terms);
		$words = $this->removeShortWords($words);
		if ($this->lang != null && $this->removeStopWords) {
			if (count($this->stopwords) == 0) {
				$this->loadStopWords();
			}
			$keywords = $this->removeStopWords($words);
		} else {
			$keywords = $words;
		}
		if ($this->lang != null && $this->doStemming) {
			if ($this->stemmer == null) {
				$this->loadStemmer();
			}
			$stems = $this->wordsToStems($keywords);
		} else {
			$stems = $keywords;
		}
		$total_results = 0;
		$total_pages = 0;
		$results = [];
		if (count($stems) > 0 && $this->tableName != null && $this->fieldsToFetch != null && $this->fieldsToMatch != null) {
			$query = '+' . implode('* +', $stems) . '*';
			$offset = ($this->pageNumber - 1) * $this->maxResultsPerPage;
			$sql_match_against = "MATCH ({$this->fieldsToMatch}) AGAINST(? IN BOOLEAN MODE)";
			$sql = "SELECT COUNT(*) AS total_results FROM {$this->tableName} WHERE $sql_match_against";
			$params = [$query];
			if (strlen($this->extraSQL) > 0) {
				$sql .= " AND ({$this->extraSQL})";
				foreach ($this->extraParams as $extraParam) {
					$params[] = $extraParam;
				}
			}
			$total_results = $this->dbQuery($sql, $params)['total_results'];
			$total_pages = ceil($total_results / $this->maxResultsPerPage);
			$sql = "SELECT {$this->fieldsToFetch}, $sql_match_against AS relevance FROM {$this->tableName} WHERE $sql_match_against";
			$params = [$query, $query];
			if (strlen($this->extraSQL) > 0) {
				$sql .= " AND ({$this->extraSQL})";
				foreach ($this->extraParams as $extraParam) {
					$params[] = $extraParam;
				}
			}
			$sql .= " ORDER BY relevance DESC LIMIT $offset, {$this->maxResultsPerPage}";
			$results = $this->dbQuery($sql, $params, true);
		}
		return [
			'currentPage' => $this->pageNumber,
			'totalPages' => $total_pages,
			'totalResults' => $total_results,
			'results' => $results,
		];
	}
}
