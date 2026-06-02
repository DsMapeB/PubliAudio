<!DOCTYPE html>
<html lang="es" class="<?php echo isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo config('APP_NAME', 'Spotify Audi'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="public/css/app.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        spotify: '#1DB954',
                        spotifyDark: '#169c46',
                        darkBg: '#121212',
                        darkCard: '#1E1E1E',
                        darkBorder: '#2A2A2A',
                        darkText: '#B3B3B3',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-darkBg dark:text-white min-h-screen antialiased">
<?php if (isset($_SESSION['usuario_id'])): ?>
<div class="flex h-screen overflow-hidden">
<?php endif; ?>
