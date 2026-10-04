# PHP Stack

> **This is a learning project.** It runs on PHP's built-in development server
> (`php -S`), which is not a production server, and it is not suitable for
> deployment as a live web application.

It shows how to build a web app from first principles, without a framework:

- **Front end:** HTML, CSS, Tailwind CSS and plain JavaScript
- **Back end:** plain PHP
- **Database:** MySQL 8.4
- **API:** endpoints that receive and return **JSON in the HTTP body**, called
  from the browser with `fetch()`
- **Server-side rendering:** some pages are built by PHP on the server, as an
  alternative to fetching JSON

It has a sister project, [python-stack](https://github.com/jongreenspan37-create/python-stack),
which builds the same app with Python and PostgreSQL. **Many of the front-end
files are identical in the two projects on purpose.** The browser code doesn't
know or care which language is behind the API, as long as the JSON responses
match.

## Running it

Everything runs in Docker, so PHP and MySQL don't need to be installed locally.

```bash
cp .env.example .env        # then change the passwords in .env
docker compose up -d --build
```

Open <http://localhost:8081>. On the **Database Interaction** page, click
**Create Tables** once to create the `roles` and `users` tables. On the
**Formula 1** page, click **Import Formula 1** to load the F1 data from the CSV
files.

Edits to the PHP, HTML, CSS and JS files take effect on the next page load with
no restart: `./app` is mounted into the container and PHP re-reads its files on
every request.

## How it works

```
browser ──fetch()──► /api/run/<file>/<function> ──► router.php ──► scripts/<file>.php
        ◄── JSON ───────────────────────────────────── returns a PHP array
```

- `app/router.php` is the front controller. Requests that don't start with
  `/api/run/` are served as static files from `app/www/`.
- API requests are looked up in an explicit `$routes` table. Only functions
  listed there can be reached. The JSON request body is decoded, the function
  is called, and its return value is sent back with `json_encode`.
- Only `app/www/` is reachable from a URL. `scripts/` and `connection.php` sit
  outside it.
- Database access uses PDO with prepared statements (`?` placeholders), so user
  input never becomes part of the SQL.

### Server-side rendering

`www/formula1.php` and `www/learning.php` are rendered on the server: PHP runs
the queries and writes the HTML before the page is sent. On the Formula 1 page,
the query dropdown and the Import button are plain HTML forms that submit back
to the page; there's no JavaScript involved. The import uses
Post/Redirect/Get, so refreshing the page doesn't run it again.

## Pages

| Page | Shows |
|---|---|
| `index.html` | Numbers, strings, string functions and date arithmetic through the API |
| `list-manipulation.html` | Reading a CSV and displaying it as a list and a table; counting and transforming items |
| `database-interaction.html` | Full CRUD for roles and users (a foreign key and a JOIN) |
| `formula1.php` | Server-rendered results of practice SQL queries on a Formula 1 dataset |
| `learning.php` | PHP basics: variables, scope, globals, arrays and string functions |

## Layout

```
app/
  router.php          front controller and API route table
  connection.php      MySQL connection (PDO), settings read from .env
  Dockerfile
  scripts/            API functions, CSV data, F1 schema and queries
  www/                pages, script.js, style.css (the only URL-reachable folder)
docker-compose.yml    the PHP app and MySQL containers
```
