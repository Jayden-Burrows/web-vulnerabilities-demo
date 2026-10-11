<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

use Ramsey\Uuid\Uuid;

// Store passwords as salted bcrypt hashes. Using salted hashes helps to prevent 
// rainbow table attacks
const BCRYPT_COST = 12;

// Bump this whenever seed_db()'s table shape or seed data changes, so a database file left
// over from an older version of this project gets rebuilt instead of causing confusing bugs
// (an older file here used plain SHA-256 passwords, which made every login fail).
const DB_SCHEMA_VERSION = 2;

// Use a hash for a random password, so when a username doesn't exist, this password can be compared 
// in password_verify() to prevent attackers from being able to guess valid usernames 
// based on the timing of the comparison.
const DUMMY_PASSWORD_HASH = '$2a$12$VHadL9VUCfKOGNelbsNnhe4fQv/dJsIZS0q3n8wKaksEIHC38CMy.';

function get_db(): PDO
{
    $dataDir = __DIR__ . '/data';
    $dbPath = $dataDir . '/demo.sqlite';

    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    $isNew = !file_exists($dbPath);
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // A file left over from an older version of this schema (for example one seeded before
    // passwords moved to bcrypt) would otherwise sit there forever, since a file that already
    // exists is never reseeded. PRAGMA user_version tags each fresh database with the schema it
    // was seeded from, so a mismatch here reseeds instead of silently failing every login.
    $version = $isNew ? null : (int) $pdo->query('PRAGMA user_version')->fetchColumn();

    if ($isNew || $version !== DB_SCHEMA_VERSION) {
        if (!$isNew) {
            $pdo = null;
            unlink($dbPath);
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        seed_db($pdo);
        $pdo->exec('PRAGMA user_version = ' . DB_SCHEMA_VERSION);
    }

    return $pdo;
}

function seed_db(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            display_name TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL,
            pass TEXT NOT NULL,
            profile_pic TEXT NOT NULL DEFAULT '/images/profile_placeholder.png'
        );

        CREATE TABLE posts (
            id TEXT PRIMARY KEY,
            author_id INTEGER NOT NULL,
            img_url TEXT NOT NULL,
            msg TEXT NOT NULL,
            loc TEXT NOT NULL DEFAULT '',
            post_date TEXT DEFAULT '',
            is_posted INTEGER NOT NULL DEFAULT 1 CHECK (is_posted IN (0, 1)),
            FOREIGN KEY (author_id) REFERENCES users(id)
        );

        CREATE TABLE saves (
            user_id INTEGER NOT NULL,
            post_id TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (post_id) REFERENCES posts(id),
            PRIMARY KEY (user_id, post_id)        
        );
    ");

    // Precompute bcrypt passwords for seed data because bcrypt is computationally expensive
    $users = [
        ['Alice Croft', 'alice_demo', 'alice@example.test', '$2a$12$zi2Eppl96QkIhvjnIrzjWeimFyyXL.FrQzt8H46Yp3/E3Gjxgns5O', '/images/profile_placeholder.png'],
        ['Bob DeBuilder', 'bob_demo', 'bob@example.test', '$2a$12$vkZrnA8D8cWNdvl3NxOErel1/U216Qsz/7ao6aNlvRXyc/1u3HaYi', '/images/profile_placeholder.png'],
        ['Carol Bell', 'carol_demo', 'carol@example.test', '$2a$12$in4dWPogq73o/anlqlyL6eiFaXSnBej7JLp6K.u6SeGRd.Fv8pkai', '/images/profile_placeholder.png'],
        ['Jimmy Carter', 'jim_demo', 'jim@example.test', '$2a$12$GkfxR9uuFbyHQeE/VnDxz.aUH5lOSj151yw7wAhGk8CSNZE.7Ua2S', '/images/profile_placeholder.png'],
        ['Eva Green', 'eva_demo', 'eva@example.test', '$2a$12$2rchn5M4bAVxU01BvroU/.efp2fMhkF043VwP6TUU7abm0CJkw0Am', '/images/profile_placeholder.png'],
        ['Frank Wright', 'frank_demo', 'frank@example.test', '$2a$12$Qu6lou0C9566SnyydSYg1.D9vOIPEthUkQak0RC.HJiXGQMrTFtXy', '/images/profile_placeholder.png'],
        ['Grace Hopper', 'grace_demo', 'grace@example.test', '$2a$12$zP63rcoa7GJwqYOKClhqm.9McglyhUsOgq02Q36vsE6aGgqMr0yKy', '/images/profile_placeholder.png'],
        ['Hank Schrader', 'hank_demo', 'hank@example.test', '$2a$12$gFap79q60VKGcL1G9UTNX.aRCdpdW5UvGQHhGfm0s9b0gsd2vON8i', '/images/profile_placeholder.png'],
        ['Ivy Chen', 'ivy_demo', 'ivy@example.test', '$2a$12$e9CgQEP7yF8Ba0gnll2.0eQoY47RHVmLRCE2LbW6v9WdHG4RW6H9a', '/images/profile_placeholder.png'],
        ['Jack Ryan', 'jack_demo', 'jack@example.test', '$2a$12$JAihH7ctOVlVjXTKaLLqUO7RJfgRDjRLCeNxkNPDT.2jaGMZkRUlC', '/images/profile_placeholder.png'],
        ['Guest', 'guest', 'guest@example.test', '$2a$12$3qZ8zMSWv0S6FbnuaTtoI.Q00xwfvCdcaewk7UrjPjSm.UhkHa3lS', '/images/profile_placeholder.png']
    ];
    $stmt = $pdo->prepare('INSERT INTO users (display_name, username, email, pass, profile_pic) VALUES (?, ?, ?, ?, ?)');
    foreach ($users as $u) {
        $stmt->execute($u);
    }

    $posts = [
        [1, '/images/uploads/beach.jpg', 'Sunny day down at the beach.', 'Myrtle Beach, South Carolina, USA', '02-03-2024', 1],
        [1, '/images/uploads/cloudy-sunset.jpg', 'Beautiful sunset over the water.', 'Caribbean Sea', '02-03-2024', 1],
        [1, '/images/placeholder.png', 'Chicago was bunz, not posting this.', 'Chicago, Illinois, USA', '', 0],
        [1, '/images/uploads/cruise.jpg', 'Morning views from the cruise.', 'Caribbean Sea', '04-12-2025', 1],
        [2, '/images/uploads/car.jpg', 'The vibes are immaculate tonight.', 'Athens, Georgia, USA', '10-06-2024', 1],
        [2, '/images/placeholder.png', 'I am so incredibly in love with Carol Bell, and I\'m so scared of her finding out. Luckily she will never see this post.', 'Athens, Georgia, USA', '', 0],
        [2, '/images/uploads/carnival.jpg', 'Foggy morning in the hills.', 'Athens, Georgia, USA', '11-15-2025', 1],
        [3, '/images/uploads/night-clouds.jpg', 'The sky is so nice tonight', 'Athens, GA, USA', '05-23-2026', 1],
        [3, '/images/placeholder.png', 'Introducing our new product! Our newest phone has amazing features such as 3D video projection, support for neural link users, and mostly importantly a new state of the art camera.', 'Athens, Georgia, USA', '', 0],
        [3, '/images/uploads/cruise-skyline.jpg', 'Leaving the port.', 'Miami, Florida, USA', '01-08-2026', 1],
        [4, '/images/uploads/dolly-truckstop.JPG', 'Just toured Dolly Parton\'s new truck stop!', 'Cornersville, Tennessee, USA', '01-17-2021', 1],
        [4, '/images/uploads/guinea-pigs.jpg', 'So small!', 'Nashville Zoo', '03-09-2022', 1],
        [4, '/images/uploads/whale.jpeg', 'So large!', 'Atlanta, Georgia, USA', '06-20-2025', 1],
        [5, '/images/uploads/night-sky.JPG', 'a beautiful night for sure', 'Athens, Georgia, USA', '08-12-2026', 1],
        [5, '/images/uploads/moon.JPG', 'another beautiful night.', 'Athens, Georgia, USA', '08-14-2026', 1],
        [6, '/images/uploads/opry.jpg', 'Exploring Tennessee today.', 'Nashville, Tennessee, USA', '09-01-2026', 1],
        [6, '/images/uploads/ramblr.jpg', 'spooky season', 'Athens, Georgia, USA', '10-30-2026', 1],
        [7, '/images/uploads/sanford.jpg', 'I\'m ready for another awesome football season!', 'Athens, Georgia, USA', '09-10-2026', 1],
        [7, '/images/uploads/sky.jpeg; /images/uploads/sunset-clouds.jpg', 'The sky is so gorgeous today...', 'Athens, Georgia, USA', '09-18-2026', 1],
        [8, '/images/uploads/sunset-clouds.jpg', 'Sunny day down at the beach.', 'Athens, Georgia, USA', '08-30-2026', 1],
        [9, '/images/uploads/turtle.jpg', 'Turtle pond!!!', 'Athens, Georgia, USA', '09-05-2026', 1],
        [9, '/images/uploads/astronaut.PNG', 'Hey, i\'m back with another piece of art! Not sure if I want to show it yet though...', 'Athens, Georgia, USA', '', 0],
        [10, '/images/placeholder.png', 'Travel journey continues in Colorado!', 'Denver, Colorado, USA', '09-22-2026', 1],
    ];

    $stmt = $pdo->prepare('INSERT INTO posts (id, author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $postUuids = [];
    // insert each post individually, assigning a unique UUID
    foreach ($posts as $i => $p) {
        $postUuids[$i + 1] = Uuid::uuid4()->toString();
        $stmt->execute([$postUuids[$i + 1], ...$p]);
    }

    $saves = [
        [1, 1],
        [1, 2],
        [1, 4],
        [1, 9],
        [1, 13],
        [1, 18],
        [2, 1],
        [2, 2],
        [2, 6],
        [2, 8],
        [2, 15],
        [2, 20],
        [3, 4],
        [3, 9],
        [3, 10],
        [3, 17],
        [4, 6],
        [4, 8],
        [4, 9],
        [4, 11],
        [4, 13],
        [5, 10],
        [5, 1],
        [5, 14],
        [5, 15],
        [5, 19],
        [6, 11],
        [6, 16],
        [6, 17],
        [7, 18],
        [7, 19],
        [7, 4],
        [7, 13],
        [8, 20],
        [8, 5],
        [8, 11],
        [9, 21],
        [9, 10],
        [9, 15],
        [10, 23],
        [10, 18],
        [10, 13]
    ];
    $stmt = $pdo->prepare('INSERT INTO saves (user_id, post_id) VALUES (?, ?)');
    foreach ($saves as [$userId, $postNumber]) {
        $stmt->execute([$userId, $postUuids[$postNumber]]);
    }
}

// Checks the username and password by comparing the password in php instead of in the SQL database
function authenticate(PDO $pdo, string $username, string $password): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, pass FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // bcrypt only reads the first 72 bytes, and nobody needs a 10 KB password:
    // refuse absurd lengths instead of spending CPU on them.
    if (strlen($password) > 256) {
        return null;
    }

    $hash = $user['pass'] ?? DUMMY_PASSWORD_HASH;
    $passwordOk = password_verify($password, $hash);

    if (!$user || !$passwordOk) {
        return null;
    }

    // If we ever raise BCRYPT_COST, upgrade each hash the next time its owner logs in.
    if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
        $upgrade = $pdo->prepare('UPDATE users SET pass = ? WHERE id = ?');
        $upgrade->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]), $user['id']]);
    }

    return ['id' => (int) $user['id'], 'username' => $user['username']];
}
function current_user_id(): int
{
    start_secure_session();

    return (int) ($_SESSION['user_id'] ?? 0);
}

function is_post_id(string $id): bool
{
    return Uuid::isValid($id);
}

function get_owned_post(PDO $pdo, string $postId, int $userId, ?int $isPosted = null): array|false
{
    $sql = 'SELECT * FROM posts WHERE id = ? AND author_id = ?';
    $params = [$postId, $userId];
    if ($isPosted !== null) {
        $sql .= ' AND is_posted = ?';
        $params[] = $isPosted;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

const post_date_sort = "substr(post_date, 7, 4) || '-' || substr(post_date, 1, 2) || '-' || substr(post_date, 4, 2)";

function get_visible_posts(PDO $pdo, array $filters = []): array
{
    $sql = 'SELECT posts.*, count(saves.post_id) as saves_count
            FROM posts
            LEFT JOIN saves ON posts.id = saves.post_id
            WHERE posts.is_posted = 1
            GROUP BY posts.id';

    $post_date_sort = "substr(post_date, 7, 4) || '-' || substr(post_date, 1, 2) || '-' || substr(post_date, 4, 2)";

    $orderBy = match ($filters['sort'] ?? 'newest') {
        'oldest' => $post_date_sort . ' ASC',
        'most-saved' => 'saves_count DESC',
        'least-saved' => 'saves_count ASC',
        default => $post_date_sort . ' DESC',
    };

    $stmt = $pdo->prepare($sql . ' ORDER BY ' . $orderBy);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_post_authors(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT DISTINCT users.id, users.display_name
         FROM users
         JOIN posts ON posts.author_id = users.id
         WHERE posts.is_posted = 1
         ORDER BY users.display_name'
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_user_own_posts(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE author_id = ? AND is_posted = 1 ORDER BY ' . post_date_sort . ' DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_user_saved_posts(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT posts.*
         FROM posts
         JOIN saves ON saves.post_id = posts.id
         WHERE saves.user_id = ? AND posts.is_posted = 1
         ORDER BY ' . str_replace('post_date', 'posts.post_date', post_date_sort) . ' DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
