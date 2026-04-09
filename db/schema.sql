PRAGMA journal_mode = WAL;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS watchers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS genres (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS films (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    imdb_id TEXT,
    tmdb_id INTEGER,
    title TEXT NOT NULL,
    year INTEGER,
    date_watched DATE,
    first_watch INTEGER NOT NULL DEFAULT 1,
    source TEXT,
    rating REAL,
    finished INTEGER NOT NULL DEFAULT 1,
    director TEXT,
    length_mins INTEGER,
    synopsis TEXT,
    certificate TEXT,
    imdb_rating REAL,
    cover_art_url TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS film_genres (
    film_id INTEGER NOT NULL,
    genre_id INTEGER NOT NULL,
    PRIMARY KEY (film_id, genre_id),
    FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS film_cast (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    film_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    character_name TEXT,
    cast_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS film_writers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    film_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS film_watchers (
    film_id INTEGER NOT NULL,
    watcher_id INTEGER NOT NULL,
    PRIMARY KEY (film_id, watcher_id),
    FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE,
    FOREIGN KEY (watcher_id) REFERENCES watchers(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_films_date_watched ON films(date_watched DESC);
CREATE INDEX IF NOT EXISTS idx_films_year ON films(year);
CREATE INDEX IF NOT EXISTS idx_films_source ON films(source);
CREATE INDEX IF NOT EXISTS idx_films_title ON films(title);
CREATE INDEX IF NOT EXISTS idx_film_cast_name ON film_cast(name);
CREATE INDEX IF NOT EXISTS idx_films_director ON films(director);

INSERT OR IGNORE INTO watchers (name) VALUES ('Nik');
INSERT OR IGNORE INTO watchers (name) VALUES ('Rob');
