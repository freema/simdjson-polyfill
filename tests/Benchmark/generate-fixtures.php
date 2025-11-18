<?php

declare(strict_types=1);

/**
 * Generate JSON fixtures for benchmarking.
 */

$fixturesDir = __DIR__ . '/fixtures';

if (!is_dir($fixturesDir)) {
    mkdir($fixturesDir, 0755, true);
}

/**
 * Generate a user object
 */
function generateUser(int $id): array
{
    $firstNames = ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry', 'Ivy', 'Jack'];
    $lastNames = ['Johnson', 'Smith', 'Brown', 'Prince', 'Martinez', 'Wilson', 'Lee', 'Davis', 'Chen', 'Taylor'];
    $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];
    $streets = ['Main St', 'Oak Ave', 'Pine Rd', 'Elm St', 'Birch Ln', 'Cedar Dr', 'Maple Ave', 'Walnut St', 'Spruce Rd', 'Ash Ln'];

    $firstName = $firstNames[$id % count($firstNames)];
    $lastName = $lastNames[$id % count($lastNames)];
    $city = $cities[$id % count($cities)];
    $street = ($id * 123) . ' ' . $streets[$id % count($streets)];

    return [
        'id' => $id,
        'name' => $firstName . ' ' . $lastName,
        'email' => strtolower($firstName) . '@example.com',
        'age' => 20 + ($id % 40),
        'active' => $id % 3 !== 0,
        'roles' => $id % 5 === 0 ? ['user', 'admin'] : ['user'],
        'address' => [
            'street' => $street,
            'city' => $city,
            'country' => 'USA',
            'zipCode' => sprintf('%05d', 10000 + $id),
        ],
        'profile' => [
            'bio' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'website' => 'https://example.com/user/' . $id,
            'phone' => sprintf('+1-555-%04d', $id),
            'joinedAt' => '2020-01-' . sprintf('%02d', 1 + ($id % 28)) . 'T10:00:00Z',
        ],
        'preferences' => [
            'theme' => $id % 2 === 0 ? 'dark' : 'light',
            'language' => 'en',
            'notifications' => [
                'email' => true,
                'push' => $id % 2 === 0,
                'sms' => false,
            ],
        ],
        'stats' => [
            'posts' => $id * 10,
            'followers' => $id * 25,
            'following' => $id * 15,
            'likes' => $id * 100,
        ],
    ];
}

/**
 * Generate fixture with target size
 */
function generateFixture(string $filename, int $targetSizeKb): void
{
    global $fixturesDir;

    $users = [];
    $currentSize = 0;
    $id = 1;

    // Generate users until we reach target size
    while ($currentSize < $targetSizeKb * 1024) {
        $user = generateUser($id++);
        $users[] = $user;
        $currentSize = strlen(json_encode(['users' => $users], JSON_PRETTY_PRINT));
    }

    $data = [
        'users' => $users,
        'metadata' => [
            'total' => count($users),
            'page' => 1,
            'perPage' => count($users),
            'hasMore' => false,
            'timestamp' => date('c'),
            'version' => '1.0',
        ],
        'stats' => [
            'activeUsers' => count(array_filter($users, fn($u) => $u['active'])),
            'inactiveUsers' => count(array_filter($users, fn($u) => !$u['active'])),
            'totalAdmins' => count(array_filter($users, fn($u) => in_array('admin', $u['roles']))),
        ],
    ];

    $json = json_encode($data, JSON_PRETTY_PRINT);
    $filepath = $fixturesDir . '/' . $filename;
    file_put_contents($filepath, $json);

    $actualSize = strlen($json);
    echo sprintf("Generated %s: %.1f KB (%d bytes)\n", $filename, $actualSize / 1024, $actualSize);
}

// Generate fixtures
echo "Generating benchmark fixtures...\n\n";

// small.json is manually created, so skip it
// generateFixture('small.json', 6);

generateFixture('medium.json', 50);
generateFixture('large.json', 100);
generateFixture('xlarge.json', 500);

echo "\nDone!\n";
