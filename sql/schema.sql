-- HeartCount web app. Import in phpMyAdmin or let includes/database.php create these tables.
CREATE DATABASE IF NOT EXISTS heart_game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE heart_game;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    total_games INT UNSIGNED NOT NULL DEFAULT 0,
    games_won INT UNSIGNED NOT NULL DEFAULT 0,
    games_lost INT UNSIGNED NOT NULL DEFAULT 0,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_username (username),
    UNIQUE KEY users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS game_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    user_answer TINYINT UNSIGNED NOT NULL,
    correct_answer TINYINT UNSIGNED NOT NULL,
    is_correct TINYINT(1) NOT NULL,
    timed_out TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY game_sessions_user (user_id),
    CONSTRAINT game_sessions_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS matches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    rounds_played INT UNSIGNED NOT NULL DEFAULT 0,
    rounds_correct INT UNSIGNED NOT NULL DEFAULT 0,
    best_streak INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'finished') NOT NULL DEFAULT 'active',
    finished_reason VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY matches_user (user_id),
    KEY matches_score (score),
    CONSTRAINT matches_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
