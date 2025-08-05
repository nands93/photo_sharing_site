CREATE DATABASE IF NOT EXISTS camagru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE camagru;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    confirmation_token VARCHAR(64) NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    reset_password_token VARCHAR(64) NULL,
    reset_password_expires DATETIME NULL,
    last_login TIMESTAMP NULL,
    notify_comments BOOLEAN DEFAULT TRUE,
    
    KEY idx_username (username),
    KEY idx_email (email),
    KEY idx_active (is_active),
    KEY idx_confirmation_token (confirmation_token)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(30) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NULL,
    mime_type VARCHAR(100) NULL,
    width INT NULL,
    height INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    upload_method VARCHAR(20) DEFAULT 'webcam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT FALSE, -- Nova coluna para controlar se está na galeria principal
    
    KEY idx_user_id (user_id),
    KEY idx_username (username),
    KEY idx_active (is_active),
    KEY idx_public (is_public), -- Novo índice
    KEY idx_created_at (created_at),
    KEY idx_upload_method (upload_method),
    
    CONSTRAINT fk_user_photos_user_id 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    username VARCHAR(30) NOT NULL,
    comment_text TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    KEY idx_photo_id (photo_id),
    KEY idx_user_id (user_id),
    KEY idx_username (username),
    KEY idx_active (is_active),
    KEY idx_created_at (created_at),
    
    CONSTRAINT fk_comments_photo_id 
        FOREIGN KEY (photo_id) REFERENCES user_photos(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user_id 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    username VARCHAR(30) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Unique constraint para prevenir likes duplicados
    UNIQUE KEY unique_photo_user (photo_id, user_id),
    
    KEY idx_photo_id (photo_id),
    KEY idx_user_id (user_id),
    KEY idx_username (username),
    KEY idx_created_at (created_at),
    
    CONSTRAINT fk_likes_photo_id 
        FOREIGN KEY (photo_id) REFERENCES user_photos(id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_user_id 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;