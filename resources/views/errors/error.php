<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (int) $status ?> <?= htmlspecialchars((string) $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
</head>
<body>
<main>
    <h1><?= (int) $status ?> <?= htmlspecialchars((string) $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
    <?php if (isset($message) && is_string($message) && $message !== ''): ?>
        <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
</main>
</body>
</html>
