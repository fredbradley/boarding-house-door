# Door Display

> _Where is Sir right now?_

A simple, always-on screen that answers the question every boarding house pupil eventually asks. No knocking on an empty door. No asking around. Just look at the display.

---

## For Pupils

When you walk past your teacher's door, the display tells you exactly where they are — whether that's in school, out on duty, at a meeting, or anywhere else. The heading updates automatically, so what you see is always current.

If they are away, the display might show a subheading too, like who to contact in the meantime.

The screen refreshes itself every minute, so you never need to reload it.

---

## For the Master

You control what the display shows from a private admin panel. There are three ways content can appear, in order of priority:

### 1. Manual entries
Schedule specific headings for specific times — _"Out of school, 2pm–4pm"_ or _"Away for the weekend"_. You can leave the end time blank for an open-ended entry. These take priority over everything else.

### 2. Google Calendar
Connect your Google Calendar via its private ICS link. When you have an event with a **Location** set, that location becomes the heading on the display. The event title stays hidden — only the location field is used. This keeps things tidy and protects your privacy.

### 3. Default
When nothing else is scheduled, the display falls back to whatever you've set as your default — typically _"In School"_. You can also set a default subheading.

### Gap alerts
Add your email address in the settings, and the system will warn you 15 minutes before your display is going to fall back to the default — handy as a nudge to log in and add an entry if you're about to be somewhere other than school.

---

## Screenshots

_Coming soon._

---

## Technical Setup

### Requirements

- PHP 8.5+
- Composer
- Node.js & npm

### Installation

```bash
# Clone the repository
git clone git@github.com:fredbradley/boarding-house-door
cd boarding-house-door

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Copy the environment file and generate an app key
cp .env.example .env
php artisan key:generate

# Create the SQLite database, run migrations and seed with initial data
touch database/database.sqlite
php artisan migrate:fresh --seed


```

The seeder creates a user (`frb@example.com` / `password`) and a screen with the slug `frb`. Update `database/seeders/ScreenSeeder.php` with real credentials before deploying.

### Running locally

```bash
# Start everything (server, queue, logs, Vite) concurrently
composer run dev
```

### Running tests

```bash
composer run test
```

### Gap alert cron

To enable the "coverage gap" email alerts, add a cron entry on your server:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

This runs the Laravel scheduler every minute, which in turn fires the gap-check command every five minutes.

### Mail

Configure your mail driver in `.env` (SMTP, Mailgun, etc.) so that gap alert emails can be delivered.

---

## Architecture notes

- **Database:** SQLite (`database/database.sqlite`)
- **Frontend:** Livewire v4 SFCs + Tailwind CSS v4
- **Calendar parsing:** `sabre/vobject`, ICS feeds cached for 15 minutes
- **Display polling:** Livewire `wire:poll.60s.keep-alive` — no WebSockets needed

---

## Routes

| URL | Visibility | Purpose |
|-----|-----------|---------|
| `/screen/{slug}` | Public | The door display |
| `/login` | Guest only | Master login |
| `/admin` | Authenticated | Manage entries, calendar, defaults |
