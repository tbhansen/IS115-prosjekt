CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_by INT NOT NULL,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Example tickets for local/demo environments.
INSERT INTO tickets (title, description, status, created_by, assigned_to)
SELECT
    'Unable to reset password',
    'The password reset link returns an invalid-token message even when opened immediately after it is requested.',
    'open',
    id,
    NULL
FROM users
WHERE id = (SELECT MIN(id) FROM users)
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE title = 'Unable to reset password'
  );

INSERT INTO tickets (title, description, status, created_by, assigned_to)
SELECT
    'Dashboard loads slowly',
    'The ticket dashboard takes more than ten seconds to load when the account contains several hundred tickets.',
    'in_progress',
    id,
    NULL
FROM users
WHERE id = (SELECT MIN(id) FROM users)
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE title = 'Dashboard loads slowly'
  );

INSERT INTO tickets (title, description, status, created_by, assigned_to)
SELECT
    'Email notifications not received',
    'New ticket notifications are shown in the application but are not arriving in the configured email inbox.',
    'open',
    id,
    NULL
FROM users
WHERE id = (SELECT MIN(id) FROM users)
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE title = 'Email notifications not received'
  );

INSERT INTO tickets (title, description, status, created_by, assigned_to)
SELECT
    'Incorrect ticket status after refresh',
    'A ticket marked as closed briefly appears as open again after the page is refreshed.',
    'closed',
    id,
    NULL
FROM users
WHERE id = (SELECT MIN(id) FROM users)
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE title = 'Incorrect ticket status after refresh'
  );

INSERT INTO tickets (title, description, status, created_by, assigned_to)
SELECT
    'Add export to CSV',
    'Provide an option to export the filtered ticket list as a CSV file for monthly reporting.',
    'open',
    id,
    NULL
FROM users
WHERE id = (SELECT MIN(id) FROM users)
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE title = 'Add export to CSV'
  );