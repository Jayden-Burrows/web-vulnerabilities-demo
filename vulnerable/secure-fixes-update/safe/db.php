<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

use Ramsey\Uuid\Uuid;

// Passwords are stored as salted bcrypt hashes (password_hash), never as plain
// text or a bare SHA-256. Each hash embeds its own random salt and cost factor.
const BCRYPT_COST = 10;

// A valid hash of a random throwaway password. When a username doesn't exist we
// still run password_verify() against this, so a login attempt takes about as
// long either way and timing can't reveal which usernames are real.
const DUMMY_PASSWORD_HASH = '$2y$10$U5bSIQGZjpZgA/Ts1p75vOBK1KOGPl37BMm.nTLDvNZKMcYF/emH2';

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

    // Pre-computed so seeding is fast (bcrypt is deliberately slow). To make one:
    //   php -r "echo password_hash('the-password', PASSWORD_BCRYPT, ['cost' => 10]);"
    $users = [
        ['Alice Croft', 'alice_demo', 'alice@example.test', '$2y$10$8RbDcwXw19ItZslwTZVjo.vF4CMjMURF/c/iW/JJJgX5GcTlCN7gu', '/images/profile_placeholder.png'],
        ['Bob DeBuilder', 'bob_demo', 'bob@example.test', '$2y$10$EXPb0NQQLLTf5/.1fDAAv.RskMJ49kevdz65Js2wWeruIlROLPomq', '/images/profile_placeholder.png'],
        ['Carol Bell', 'carol_demo', 'carol@example.test', '$2y$10$Fk3D0eqKcneqPkIUQY/YcujU/AyKsyXnHqZE/KR61KKApQaKn6Zom', '/images/profile_placeholder.png'],
        ['Jimmy Carter', 'jim_demo', 'jim@example.test', '$2y$10$VjjVN3IXtKJbpAax77XiEO2d2DNkS.wXEvHXz9zIYev6yRyZhsCFu', '/images/profile_placeholder.png'],
        ['Eva Green', 'eva_demo', 'eva@example.test', '$2y$10$rAg.adLfb9MPuzGtjLt/xudw458s8nN16LxR2ymR7Vn3Z1IucgXMu', '/images/profile_placeholder.png'],
        ['Frank Wright', 'frank_demo', 'frank@example.test', '$2y$10$J7lDpEM8D3Ct45vJaq0zmuc0TB9mVkmv00oayrwqVYkMU.6gi/AFK', '/images/profile_placeholder.png'],
        ['Grace Hopper', 'grace_demo', 'grace@example.test', '$2y$10$JQLJLSGl7j.nNOdFi5LqW.j9bkmvUkwdgDEhQJBzFHqamSC1VU17m', '/images/profile_placeholder.png'],
        ['Hank Schrader', 'hank_demo', 'hank@example.test', '$2y$10$KXzbjVAvgGx91mVSe5kGk.zHVdF6ULB7yIYBdGdgSVJeEhdO/bWXm', '/images/profile_placeholder.png'],
        ['Ivy Chen', 'ivy_demo', 'ivy@example.test', '$2y$10$O2qTpwe.1GxjGQ2Shm/mLe2SE6Zcy0anism31Q3Fb/AtO21mqIfCe', '/images/profile_placeholder.png'],
        ['Jack Ryan', 'jack_demo', 'jack@example.test', '$2y$10$0krgkHvrPLFYwZ2kAdUzWeosY9o29QlkHLOdpiu3vfGNzJyL2AQZC', '/images/profile_placeholder.png'],
    ];
    $stmt = $pdo->prepare('INSERT INTO users (display_name, username, email, pass, profile_pic) VALUES (?, ?, ?, ?, ?)');
    foreach ($users as $u) {
        $stmt->execute($u);
    }

    $posts = [
        [1, '/images/uploads/low-quality-sebastien-lavalaye-TivcHM0QYNg-unsplash.jpg', 'Excited to share this!', 'Portland, Oregon, USA', '02-03-2024', 1],
        [1, '/images/uploads/jakub-velicka-DrH2WT-oa7I-unsplash.jpg', 'Another update from me.', '', '02-03-2024', 1],
        [1, '/images/uploads/low-quality-mihai-FvMe55sjXOI-unsplash.jpg', 'Draft: not ready yet.', 'Portland, Oregon, USA', '', 0],
        [1, '/images/uploads/low-quality-federico-giampieri-VN89CJ5bf6c-unsplash.jpg', 'Morning views near the lake.', 'Bend, Oregon, USA', '04-12-2025', 1],
        [2, '/images/uploads/low-quality-ahmed-hossam-B1x3KYNgae0-unsplash.jpg', 'Bob here, just posted.', 'Austin, Texas, USA', '10-06-2024', 1],
        [2, '/images/uploads/low-quality-karsten-winegeart-tXT4nd_crWk-unsplash.jpg', 'Bob draft, still editing.', 'Austin, Texas, USA', '', 0],
        [2, '/images/uploads/low-quality-sebastien-lavalaye-KZVNZxIDrnM-unsplash.jpg', 'Foggy morning in the hills.', 'Austin, Texas, USA', '11-15-2025', 1],
        [3, '/images/uploads/lei-hwang-Z9PLpbYriJo-unsplash.jpg', 'Carol says hi.', 'Chicago, Illinois, USA', '05-23-2026', 1],
        [3, '/images/uploads/juho-luomala-hbof6F8T72E-unsplash.jpg', 'Carol private draft.', 'Chicago, Illinois, USA', '', 0],
        [3, '/images/uploads/low-quality-denis-z2OZWfwYxbU-unsplash.jpg', 'City lights from the high rise.', 'Chicago, Illinois, USA', '01-08-2026', 1],
        [4, '/images/uploads/low-quality-tanaphong-toochinda-fakXx42emDU-unsplash.jpg', 'What a cool picture I took!', 'San Francisco, California, USA', '01-17-2021', 1],
        [4, '/images/uploads/fatih-berat-orer-QXUmmF0rHWc-unsplash.jpg;/images/uploads/low-quality-pavel-rysych-6p0nivYmTAE-unsplash.jpg;/images/uploads/richard-stachmann-JprJHXI9FaE-unsplash.jpg', 'These are some cool pics I took!', 'San Francisco, California, USA', '03-09-2022', 1],
        [4, '/images/uploads/low-quality-anupam-raisim-kerketta-hxIO21-unYQ-unsplash.jpg', 'Golden Gate Bridge never disappoints.', 'San Francisco, California, USA', '06-20-2025', 1],
        [5, '/images/uploads/low-quality-marcin-kempa-zrWyj0NBupA-unsplash.jpg', 'Greetings from New York!', 'New York City, New York, USA', '08-12-2026', 1],
        [5, '/images/uploads/low-quality-robert-heiser-4bT8pczLFfw-unsplash.jpg', 'Times Square vibes at night.', 'New York City, New York, USA', '08-14-2026', 1],
        [6, '/images/uploads/low-quality-jocke-wulcan-KLOW1bD616Y-unsplash.jpg', 'Exploring Seattle today.', 'Seattle, Washington, USA', '09-01-2026', 1],
        [6, '/images/uploads/low-quality-herbert-goetsch-YEcnWZkI67o-unsplash.jpg', 'Space Needle looking majestic.', 'Seattle, Washington, USA', '09-02-2026', 1],
        [7, '/images/uploads/low-quality-jardel-vieira-HPUgWtdgzZ4-unsplash.jpg', 'Quiet walk in the autumn forest.', 'Boston, Massachusetts, USA', '09-10-2026', 1],
        [7, '/images/uploads/low-quality-juan-carlos-pavon-xkKFhBYA5VI-unsplash.jpg', 'National Park road trip shot!', 'Yosemite, California, USA', '09-18-2026', 1],
        [8, '/images/uploads/low-quality-fast-ink-33my-dJZ3N4-unsplash.jpg', 'Sunny day down at the beach.', 'Miami, Florida, USA', '08-30-2026', 1],
        [9, '/images/uploads/low-quality-thom-milkovic-_FFQs6O8u34-unsplash.jpg; /images/uploads/ivan-shimko-vGQvjhvYiEU-unsplash.jpg', 'Cat sleeping on my workspace setup.', 'Los Angeles, California, USA', '09-05-2026', 1],
        [9, '/images/uploads/dmytro-bayer-EoCZ54UVhec-unsplash.jpg', 'Late night coding marathon.', 'Los Angeles, California, USA', '', 0],
        [10, '/images/uploads/low-quality-fast-ink-d-0BdkpompM-unsplash.jpg; /images/uploads/alice-qu-EmoxrAwZxHc-unsplash.jpg', 'Travel journey continues in Colorado!', 'Denver, Colorado, USA', '09-22-2026', 1],
    ];

    $stmt = $pdo->prepare('INSERT INTO posts (id, author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $postUuids = []; // position in $posts (1-based) => uuid, so seeded saves can point at them
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

/**
 * Check a username + password. Returns ['id' => ..., 'username' => ...] or null.
 * The username goes in as a bound parameter; the password is never put in SQL at
 * all, it is compared in PHP with password_verify().
 */
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

/** True if $id looks like a UUID. Cheap guard before touching the database. */
function is_post_id(string $id): bool
{
    return Uuid::isValid($id);
}

/**
 * Fetch a post only if it belongs to $userId. This is THE ownership check:
 * every read/modify/delete of a draft goes through it (or has
 * "AND author_id = ?" in its SQL), so a guessed or leaked ID is useless.
 * $isPosted: 1 = published only, 0 = drafts only, null = either.
 */
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

/** SQL expression turning the stored MM-DD-YYYY text into sortable YYYY-MM-DD. */
const POST_DATE_SQL = "substr(post_date, 7, 4) || '-' || substr(post_date, 1, 2) || '-' || substr(post_date, 4, 2)";

function get_visible_posts(PDO $pdo, array $filters = []): array
{
    $sql = 'SELECT posts.*, count(saves.post_id) as saves_count
            FROM posts
            LEFT JOIN saves ON posts.id = saves.post_id
            WHERE posts.is_posted = 1
            GROUP BY posts.id';

    $postDateSort = POST_DATE_SQL;

    // Allow-list: the sort value only ever selects one of these fixed strings;
    // user input is never concatenated into the SQL.
    $orderBy = match ($filters['sort'] ?? 'newest') {
        'oldest'      => $postDateSort . ' ASC',
        'most-saved'  => 'saves_count DESC',
        'least-saved' => 'saves_count ASC',
        default       => $postDateSort . ' DESC',
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
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE author_id = ? AND is_posted = 1 ORDER BY ' . POST_DATE_SQL . ' DESC');
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
         ORDER BY ' . str_replace('post_date', 'posts.post_date', POST_DATE_SQL) . ' DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
