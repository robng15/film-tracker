# Deployment Guide

## Requirements
- PHP 8.0+
- SQLite3 extension (`php-sqlite3`)
- `allow_url_fopen = On` (for API calls)

## Steps

### 1. Clone the repo
```bash
git clone https://github.com/robng15/film-tracker.git
cd film-tracker
```

### 2. Create config.php
```bash
cp includes/config.example.php includes/config.php
```
Edit `includes/config.php` and set your OMDb and TMDB API keys.

### 3. Create the database directory
```bash
mkdir -p db
chmod 755 db
```

### 4. Run setup
Visit `https://your-domain/setup.php` in a browser to create the database and admin account.

### 5. Plesk — document root
Set the document root for the domain to the `film-tracker/` folder (the directory containing `index.php`).

### 6. Plesk — Git deployment (optional)
In Plesk → Git, add the repository URL and set the deployment path. After each `git pull`, re-copy or update `includes/config.php` if needed (it is not tracked in git).

## File permissions
The `db/` directory must be writable by the web server:
```bash
chmod 755 db
```
