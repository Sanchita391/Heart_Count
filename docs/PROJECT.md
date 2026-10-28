# HeartCount web project

This note explains the working web application. It follows the Flutter HeartCount screens and uses the Heart API.

The app is Heart Game. A picture shows hearts. The player counts them and presses 0 to 9, the same as the Flutter game screen.

## What the application does

A player signs in, asks for a puzzle, looks at the picture, and enters how many hearts they see. Practice mode keeps a score of one point per correct answer. Challenge mode adds a timer, lives and a leaderboard. An admin can see players and remove a normal user.

## Screen flow, matched to the Flutter app

The Flutter app used one screen file for each step. This web app uses one PHP page for the same step.

| Flutter screen | Web page | What it does |
| --- | --- | --- |
| Splash | `index.php` | Shows the title, then moves on |
| Login | `login.php` | Checks email and password |
| Register | `register.php` | Creates a normal user |
| Home | `home.php` | Shows score and the game buttons |
| Game | `game.php` | Practice: picture, digit buttons, score |
| Profile | `profile.php` | Change username, email or password |
| Statistics | `stats.php` | Shows this player's practice results |

Three extra pages were added for this web version:

| Web page | Why it is there |
| --- | --- |
| `challenge.php` | Timed match with lives |
| `leaderboard.php` | Finished challenge scores |
| `admin.php` | Admin-only player list |

The Flutter app kept game state in a provider and called its own PHP API. This version keeps the signed-in player in a PHP session, and the browser talks only to the PHP pages in this project.

## Distributed architecture

Four parts work together. They do not all run in the browser.

1. The browser shows the pages and listens for clicks. Files: `assets/js/game.js`, `assets/js/challenge.js`, `assets/js/auth.js`.
2. PHP on XAMPP checks the login, stores the correct digit, and talks to the database. Files: `includes/` and `api/`.
3. MySQL stores users, practice answers and challenge matches. File: `sql/schema.sql`.
4. The Heart API is a separate website. PHP requests a puzzle from it.

```
Browser  -- click or form -->  PHP (XAMPP)
PHP      -- SQL ----------->  MySQL
PHP      -- HTTP JSON ----->  Heart API
PHP      -- image URL only ->  Browser
```

The correct count is stored in the PHP session. The browser receives the picture, not the answer, until the player has chosen a number.

## Heart API

The request is in `includes/config.php`:

`https://marcconrad.com/uob/heart/api.php?out=json`

There is no API key. The response is JSON with two fields:

- `question`: the address of the picture
- `solution`: how many hearts are in the picture, from 0 to 9

PHP reads that response in `request_puzzle()` inside `includes/game_service.php`. It checks that the picture address is HTTPS and belongs to `sanfoh.com` or `marcconrad.com`. If the request fails, PHP tries three times, then the page shows an error and a Retry button.

The Flutter HeartCount app used this same Heart API. The web app keeps that API so the game stays a heart-counting game.

## The four themes in this code

### Version control

The project is a Git repository. Each page, the API files and the database script are separate files, so a change to the game does not require editing the login page. Commits should describe one change, such as adding login or connecting the Heart API.

### Interoperability

PHP and the Heart API are different programs on different machines. They work together through HTTP and JSON. The browser never calls the Heart API itself. `api/game.php` and `api/challenge.php` call `fetch_puzzle()`, then send the picture address to the page.

### Event-driven programming

The pages wait for an action, then run a function.

| Action | File | What runs |
| --- | --- | --- |
| Show or hide password | `assets/js/auth.js` | click on the eye button |
| Login or register submit | `login.php`, `register.php` | form submit, then PHP checks the values |
| Digit button | `assets/js/game.js` | `submitAnswer()` |
| Keys 0 to 9 | `assets/js/game.js` | the same `submitAnswer()` |
| Refresh or Retry | `assets/js/game.js` | `loadPuzzle()` |
| Challenge timer | `assets/js/challenge.js` | a one-second timer updates the clock |

After a practice answer, a one-second timer shows the result and then loads the next picture.

### Virtual identity

Authentication means the app checks who the person is. Authorization means it checks what that person may open.

- Registration stores `password_hash()`, never the plain password.
- Login uses `password_verify()` and then `login_user()` in `includes/session.php`.
- PHP sends an `HttpOnly` session cookie. JavaScript cannot read it.
- Login, register, profile and delete forms include a CSRF token.
- Every game and profile page calls `require_login()`.
- `admin.php` calls `require_admin()`. A normal user who types that address is sent back to home.
- A new account is always role `user`. The register form has no role field.
- An admin cannot delete another admin or their own account.

Roles:

| Role | Can play | Can open Manage Players | Can delete a normal user |
| --- | --- | --- | --- |
| user | yes | no | no |
| admin | yes | yes | yes |

## Practice and challenge

Practice (`game.php`) is the Flutter heart-counting game:

- unlimited rounds
- one point for a correct count
- score, games played and accuracy on the page

Challenge (`challenge.php`) is the extra timed mode:

| Level | Time | Lives | Rounds |
| --- | --- | --- | --- |
| Easy | 20 seconds | 3 | 8 |
| Medium | 12 seconds | 3 | 10 |
| Hard | 7 seconds | 2 | 12 |

Points are calculated on the server from the remaining time and the streak. A wrong digit or a timeout removes one life. The match ends when the lives are gone or the last round is finished.

## How to run it

1. Start Apache and MySQL in XAMPP.
2. This PC uses MariaDB on port 3307 because another MySQL service uses port 3306. That port is set in `includes/config.php`.
3. Open `http://localhost/heart_count/`.
4. The first visit creates the `heart_game` database from `sql/schema.sql`.

Accounts already used for testing:

- Player: `heartweb@example.com` / `heart123`
- Admin: `admin@gmail.com` / `123456`

## Where the main code lives

| Job | File |
| --- | --- |
| Database settings and Heart API address | `includes/config.php` |
| Load a puzzle and save a practice answer | `includes/game_service.php` |
| Start a challenge, score a round, leaderboard | `includes/challenge_service.php` |
| Login session and role checks | `includes/session.php` |
| Practice request and answer check | `api/game.php` |
| Challenge request and answer check | `api/challenge.php` |
| Tables | `sql/schema.sql` |

## Sources

- Heart API by Dr Marc Conrad: `https://marcconrad.com/uob/heart/api.php?out=json`
- The screen order comes from the existing HeartCount Flutter app: splash, login, register, home, game, profile, statistics.
- The web pages, PHP and JavaScript were written with AI assistance in Cursor. The external API and the assisted code are marked in `includes/bootstrap.php` and `includes/config.php`.
