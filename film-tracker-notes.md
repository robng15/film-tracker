# Film Tracker — Project Notes

## Overview
A web app for tracking film watching, with data pulled from OMDb/TMDB APIs.
Single user login. SQLite DB, PHP backend, Bootstrap frontend.

---

## API Strategy

Both APIs are needed:
- **OMDb API** (omdbapi.com) — primary metadata lookup. Free tier: 1,000 req/day.
  - Returns: title, year, UK certificate rating, runtime, genre, director, writer, plot, poster, imdbRating, imdbID
- **TMDB API** (themoviedb.org) — used exclusively for full cast (top 20). OMDb only returns ~4 actors.
- All API-populated fields remain manually editable in case of no match or bad data
- Certificate ratings use UK format (U, PG, 12A, 15, 18)

---

## Database Fields

| Field            | Source     | Notes                                              |
|------------------|------------|----------------------------------------------------|
| cover_art        | API        | Poster URL from OMDb/TMDB. Display at 100px wide   |
| date_watched     | Manual     | Auto-fills to today's date on new entry            |
| title            | API/Manual | Links to IMDB page when clicked                    |
| year             | API        | Release year of film                               |
| first_watch      | Manual     | Boolean                                            |
| source           | Manual     | See Source options below                           |
| rating           | Manual     | 0–10 in 0.5 increments. Blank on creation          |
| finished         | Manual     | Boolean                                            |
| watchers         | Manual     | Multi-select: Nik, Rob                             |
| director         | API        | Editable                                           |
| genre            | API        | Stored as separate tags (see Genre notes)          |
| length           | API        | Runtime in minutes                                 |
| synopsis         | API        | Plot field from OMDb                               |
| certificate      | API        | Rated field from OMDb (U, PG, 12A, 15, 18, etc.)  |
| imdb_rating      | API        | e.g. 7.4/10                                        |
| cast             | API        | Top 20, from TMDB credits endpoint                 |
| writers          | API        | From OMDb Writer field                             |
| imdb_id          | API        | Stored for deep linking and re-fetching            |

---

## Field Behaviour

### Cover Art
- Pulled from API, stored as URL
- Displayed at 100px wide in list/detail view

### Date Watched
- Auto-populates to today's date on new manual entry
- Editable

### Title
- Clicking title links to `https://www.imdb.com/title/{imdb_id}`

### Source (manual dropdown)
- Netflix
- Firestick
- Amazon Prime
- Cinema
- Paramount+
- DVD/Blu-ray
- Other (free text entry)

### Rating
- Scale: 0–10 in 0.5 increments (0, 0.5, 1.0 ... 10.0)
- Blank/null on record creation

### Watchers
- Multi-select checkboxes, populated from a configurable `watchers` table
- Default watchers: Nik, Rob
- Manage watchers via admin/settings UI (add, rename, remove)

### Genre
- Stored as individual tags (not a single comma-separated string)
- Allows filtering by individual genre
- e.g. "Action, Thriller" → two separate tag records

---

## UI / Frontend

- PHP + Bootstrap
- Single user login (session-based auth)
- All columns sortable
- Filters:
  - Year from Date Watched
  - Year of Film Released
  - Source
  - Genre (tag-based)
- Search function for Director and Cast member

---

## Import / Export

- **Export:** CSV download of all records
- **Import:** CSV upload
  - On import, system attempts title+year match against OMDb API
  - Matched results shown for review before committing to DB
  - Unmatched or ambiguous entries flagged for manual lookup
  - All fields remain editable post-import

### Existing data.csv
- 191 records, date range Jan 2023 – Apr 2026
- Format: Date Watched (DD/MM/YYYY), Title, Year, First Watch, Source, Rating, Finished, Watchers
- API fields (director, genre, cast etc.) are all blank — will be populated on import
- Known issues — all corrected in data.csv:
  - `Cowboys & Aliens` — year fixed 2023 → 2011
  - `Creep` (2002) — year fixed → 2004 (UK horror film)
  - `Nosfaratu` — corrected to `Nosferatu` (2024)
  - `Listen Carefully/Baby Monitor` — renamed to `Baby Monitor` (2024)
- Some very recent/future-dated films may not be in OMDb yet — flag these on import

---

## Technical Stack

- **Database:** SQLite
- **Backend:** PHP
- **Frontend:** Bootstrap
- **Auth:** Single user login

---

## APIs

### OMDb (paid tier)
- Key: `8637e5c6`
- Lookup by IMDB ID: `http://www.omdbapi.com/?i={imdb_id}&apikey=8637e5c6`
- Lookup by title+year: `http://www.omdbapi.com/?t={title}&y={year}&apikey=8637e5c6`
- Poster image: `http://img.omdbapi.com/?i={imdb_id}&h=600&apikey=8637e5c6`

### TMDB (for top 20 cast)
- Key: TBC — register at themoviedb.org
- Search: `https://api.themoviedb.org/3/search/movie?query={title}&year={year}&api_key={key}`
- Credits: `https://api.themoviedb.org/3/movie/{tmdb_id}/credits?api_key={key}`

---

## Resolved

- [x] OMDb API key — paid tier: `8637e5c6`
- [x] TMDB API key: `c4fb4e961840de9f55deef132bd64265`
- [x] TMDB Read Access Token: stored in config.php
- [x] Certificate ratings: UK format
- [x] Watchers: configurable via settings UI
