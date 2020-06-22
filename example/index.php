<?php

if (isset($_GET['query'])) {
	require '../vendor/autoload.php';
	require 'config.php';
	require '../mysql-search.php';
	$search = new Search();
	$search->setLang('en');
	$search->setRemoveStopWords(true);
	$search->setDoStemming(true);
	$search->setDatabaseHandler($dbh);
	$search->setTableName('quotes');
	$search->setFieldsToMatch('quote', 'author');
	$search->setFieldsToFetch('*');
	if (isset($_GET['page'])) {
		$search->setPageNumber($_GET['page']);
	}
	$search->setMaxResultsPerPage(3);
	$results = $search->exec($_GET['query']);

	header('Content-Type: application/json');
	print json_encode($results);
	exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
	<title>Live Search Example</title>
	<style>
		main {
			width: 960px;
			margin: 0 auto;
		}

		input {
			font-size: 20px;
			display: block;
			width: 100%;
			padding: 0 1em;
			line-height: 2em;
			border: 1px solid #ddd;
			box-sizing: border-box;
			margin: 20px 0;
			border-radius: 1em;
		}

		input:focus {
			outline: none;
			box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
		}

		blockquote {
			margin: 30px 0;
			font-size: 1.4em;
			font-family: Open Sans;
			font-style: italic;
			color: #555555;
			padding: 1.2em 30px 1.2em 75px;
			border-left: 8px solid #78C0A8;
			line-height: 1.6;
			position: relative;
			background: #f5f5f5;
		}

		blockquote::before {
			font-family: Arial;
			content: "\201C";
			color: #78C0A8;
			font-size: 4em;
			position: absolute;
			left: 10px;
			top: -10px;
		}

		blockquote::after {
			content: '';
		}

		blockquote strong {
			display: block;
			color: #333333;
			font-style: normal;
			font-weight: bold;
			margin-top: 1em;
		}

		.message,
		.pages {
			text-align: center;
		}

		.page {
			display: inline-block;
			color: #666666;
			font-size: 18px;
			line-height: 24px;
			padding: 0 8px;
			text-decoration: none;
			margin: 0 2px;
			text-shadow: 0px 1px 0px #ffffff;
		}

		.page:not(.current) {
			box-shadow: inset 0px 1px 0px 0px #ffffff;
			background: linear-gradient(to bottom, #ffffff 5%, #f6f6f6 100%);
			background-color: #ffffff;
			border-radius: 6px;
			border: 1px solid #dcdcdc;
			cursor: pointer;
		}

		.current.page {
			font-weight: bold;
			font-size: 20px;
			background: transparent;
			border: 0;
		}

		.page:not(.current):hover {
			background: linear-gradient(to bottom, #f6f6f6 5%, #ffffff 100%);
			background-color: #f6f6f6;
		}

		.page:not(.current):active {
			position: relative;
			top: 1px;
		}

		.page:focus {
			outline: none;
		}
	</style>
</head>

<body>
	<main>
		<input type="search" v-model="searchQuery" @input="debounceSearch($event.target.value)" placeholder="Search for famous quotes">
		<div v-if="searchQuery.trim().length > 0">
			<div v-if="results.length > 0">
				<blockquote v-for="result in results">
					{{result.quote}}
					<strong>{{result.author}}</strong>
				</blockquote>
			</div>
			<div class="message" v-else>
				<strong>There is no result</strong>
			</div>
			<div class="pages" v-if="totalPages > 1">
				<button v-for="pageNumber in totalPages" class="page" :class="{current: pageNumber == currentPage}" @click="changeCurrentPage(pageNumber)">
					{{pageNumber}}
				</button>
			</div>
		</div>
	</main>
	<script>
		// Prevent multiple requests to server by delaying text input
		function debounce(fn, delay) {
			var timeoutID = null
			return function() {
				clearTimeout(timeoutID)
				var args = arguments
				var that = this
				timeoutID = setTimeout(function() {
					fn.apply(that, args)
				}, delay)
			}
		}

		new Vue({
			el: 'main',
			data: function() {
				return {
					searchQuery: '',
					results: [],
					currentPage: 1,
					totalPages: 0,
				};
			},
			methods: {
				changeCurrentPage: function(pageNumber) {
					if (pageNumber != this.currentPage) {
						this.currentPage = pageNumber;
						this.debounceSearch(this.searchQuery, this.currentPage);
					}
				},

				debounceSearch: debounce(function(searchQuery, pageNumber = 1) {
					this.searchQuery = searchQuery;
					if (searchQuery.trim().length == 0) {
						this.results = [];
						this.currentPage = 1;
						this.totalPages = 0;
					} else {
						this.currentPage = pageNumber;
						fetch('?query=' + encodeURIComponent(searchQuery) + '&page=' + this.currentPage)
							.then(response => response.json()).then(data => {
								this.results = data.results;
								this.currentPage = data.currentPage;
								this.totalPages = data.totalPages;
							});
					}
				}, 500)
			}
		});
	</script>
</body>

</html>