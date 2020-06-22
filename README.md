# mysql-search.php
PHP script that implements a search algorithm using MySQL Full Text Search feature.

**The algorithm:**
1. Take the search text string and split it into a list of words.
2. Remove all "stopwords" (See: https://en.wikipedia.org/wiki/Stop_words) from the previous list. The remaining words will be called "keywords".
3. Apply a stemming algorithm over the list of keywords (See: https://en.wikipedia.org/wiki/Stemming).
4. Construct and execute an SQL query (SELECT) over an arbitrary list of "Full Text Search" indexed fields (MATCH) and using the previous list of words stems (AGAINST).
5. Fetch the obtained search results.

There is an example of use in "example" folder. The frontend part of the example is implemented using VueJS framework.

**How to deploy example:**
1. Clone (or download and unzip) this repository.
2. Install Composer, if you don't have it installed (See: https://getcomposer.org/).
3. Open the repository folder in a terminal console and run the following command: "composer install"
4. Create a MySQL database called "test" and import the "test.sql" file (found in "example" folder).
5. See "config.php" (found in "example" folder) file comments and choose between PDO handler and MySQLi handler.
6. Open a web browser and navigate to "example" folder.
