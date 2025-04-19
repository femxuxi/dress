-- Create database (if not already created)
CREATE DATABASE IF NOT EXISTS u659181579_dressmaria;
USE u659181579_dressmaria;

-- Users table for authentication
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'designer', 'customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Admin profile table
CREATE TABLE admin_profiles (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Designer profile table
CREATE TABLE designer_profiles (
    designer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    specialization VARCHAR(100),
    experience_years INT,
    bio TEXT,
    profile_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Customer profile table
CREATE TABLE customer_profiles (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    wedding_date DATE,
    profile_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Dress categories
CREATE TABLE dress_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Dresses table
CREATE TABLE dresses (
    dress_id INT AUTO_INCREMENT PRIMARY KEY,
    designer_id INT NOT NULL,
    category_id INT NOT NULL,
    dress_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    size_available VARCHAR(255),
    color_options TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (designer_id) REFERENCES designer_profiles(designer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES dress_categories(category_id)
);

-- Appointments table
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    designer_id INT NOT NULL,
    dress_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration INT NOT NULL DEFAULT 60, -- Duration in minutes
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_profiles(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (designer_id) REFERENCES designer_profiles(designer_id) ON DELETE CASCADE,
    FOREIGN KEY (dress_id) REFERENCES dresses(dress_id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    designer_id INT,
    dress_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_profiles(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (designer_id) REFERENCES designer_profiles(designer_id) ON DELETE SET NULL,
    FOREIGN KEY (dress_id) REFERENCES dresses(dress_id) ON DELETE SET NULL
);

-- Insert default admin user
INSERT INTO users (email, password, user_type) VALUES 
('admin@dressmaria.com', '$2y$10$1qAz2wSx3eDc4rFv5tGb5eOcBZO4hjoCsh5krEI9/UHJbQEYaRD8i', 'admin');
-- Default password is 'Admin@123' (hashed with bcrypt)

INSERT INTO admin_profiles (user_id, first_name, last_name, phone) VALUES 
(1, 'Admin', 'User', '123-456-7890');

-- Insert sample dress categories
INSERT INTO dress_categories (category_name, description) VALUES
('A-Line', 'A fitted bodice with a skirt that extends outward in the shape of an "A"'),
('Ball Gown', 'A fitted bodice with a full skirt, often with layers of tulle or other fabric'),
('Mermaid', 'A fitted silhouette throughout the bodice, hips, and thighs that flares out below the knee'),
('Sheath', 'A narrow, form-fitting dress that follows the body\'s natural line'),
('Tea Length', 'A dress with a hemline that falls between the knee and ankle');
