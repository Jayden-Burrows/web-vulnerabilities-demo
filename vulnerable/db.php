<?php

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

    if ($isNew) {
        seed_db($pdo);
    }

    return $pdo;
}

function seed_db(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            display_name TEXT NOT NULL,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            pass TEXT NOT NULL,
            profile_pic TEXT NOT NULL DEFAULT '/images/profile_placeholder.png'
        );

        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            post_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (post_id) REFERENCES posts(id),
            PRIMARY KEY (user_id, post_id)        
        );
    ");

    $users = [
        ['Alice Croft', 'alice_demo', 'alice@example.test', hash('sha256', 'password'), '/images/profile_placeholder.png'],
        ['Bob DeBuilder', 'bob_demo', 'bob@example.test', hash('sha256', '12345678'), '/images/profile_placeholder.png'],
        ['Carol Bell', 'carol_demo', 'carol@example.test', hash('sha256', 'abcd1234'), '/images/profile_placeholder.png'],
        ['Jimmy Carter', 'jim_demo', 'jim@example.test', hash('sha256', 'pokemon1'), '/images/profile_placeholder.png'],
        ['Eva Green', 'eva_demo', 'eva@example.test', hash('sha256', 'mypassword'), '/images/profile_placeholder.png'],
        ['Frank Wright', 'frank_demo', 'frank@example.test', hash('sha256', 'welcome1'), '/images/profile_placeholder.png'],
        ['Grace Hopper', 'grace_demo', 'grace@example.test', hash('sha256', 'compiler1'), '/images/profile_placeholder.png'],
        ['Hank Schrader', 'hank_demo', 'hank@example.test', hash('sha256', 'minerals'), '/images/profile_placeholder.png'],
        ['Ivy Chen', 'ivy_demo', 'ivy@example.test', hash('sha256', 'design2026'), '/images/profile_placeholder.png'],
        ['Jack Ryan', 'jack_demo', 'jack@example.test', hash('sha256', 'analyst99'), '/images/profile_placeholder.png'],
        ['Guest', 'guest', 'guest@example.test', hash('sha256', 'password'), '/images/profile_placeholder.png']
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
        [5, '/images/uploads/moon.jpg', 'another beautiful night.', 'Athens, Georgia, USA', '08-14-2026', 1],
        [6, '/images/uploads/opry.jpg', 'Exploring Tennessee today.', 'Nashville, Tennessee, USA', '09-01-2026', 1],
        [6, '/images/uploads/ramblr.jpg', 'spooky season', 'Athens, Georgia, USA', '10-30-2026', 1],
        [7, '/images/uploads/sanford.jpg', 'I\'m ready for another awesome football season!', 'Athens, Georgia, USA', '09-10-2026', 1],
        [7, '/images/uploads/sky.jpeg; /images/uploads/sunset-clouds.jpg', 'The sky is so gorgeous today...', 'Athens, Georgia, USA', '09-18-2026', 1],
        [8, '/images/uploads/sunset-clouds.jpg', 'Sunny day down at the beach.', 'Athens, Georgia, USA', '08-30-2026', 1],
        [9, '/images/uploads/turtle.jpg', 'Turtle pond!!!', 'Athens, Georgia, USA', '09-05-2026', 1],
        [9, '/images/uploads/astronaut.PNG', 'Hey, i\'m back with another piece of art! Not sure if I want to show it yet though...', 'Athens, Georgia, USA', '', 0],
        [10, '/images/placeholder.png', 'Travel journey continues in Colorado!', 'Denver, Colorado, USA', '09-22-2026', 1],
    ];

    $stmt = $pdo->prepare('INSERT INTO posts (author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($posts as $p) {
        $stmt->execute($p);
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
    foreach ($saves as $s) {
        $stmt->execute($s);
    }
}

function current_user_id(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return (int) $_SESSION['user_id'] ?? 0;
}

function get_visible_posts(PDO $pdo, array $filters = []): array
{
    $sql = 'SELECT posts.*, count(saves.post_id) as saves_count
            FROM posts
            LEFT JOIN saves ON posts.id = saves.post_id
            WHERE posts.is_posted = 1
            GROUP BY posts.id';

    $sort_param = $filters['sort'];

    $post_date_sort = "substr(post_date, 7, 4) || '-' || substr(post_date, 1, 2) || '-' || substr(post_date, 4, 2)";

    switch ($sort_param) {
        case 'newest':
            $sql .= ' ORDER BY ' . $post_date_sort . 'DESC';
            break;
        case 'oldest':
            $sql .= ' ORDER BY ' . $post_date_sort . 'ASC';
            break;
        case 'most-saved':
            $sql .= ' ORDER BY saves_count DESC';
            break;
        case 'least-saved':
            $sql .= ' ORDER BY saves_count ASC';
            break;
    }

    $stmt = $pdo->prepare($sql);
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
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE author_id = ? AND is_posted = 1 ORDER BY id DESC');
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
         ORDER BY posts.id DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}