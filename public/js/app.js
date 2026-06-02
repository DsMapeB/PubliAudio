document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
});

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    document.cookie = 'dark_mode=' + isDark + ';path=/;max-age=' + 365*24*60*60;
}

function initDarkMode() {
    const isDark = document.cookie.split('; ').find(row => row.startsWith('dark_mode='));
    if (isDark) {
        const value = isDark.split('=')[1];
        if (value === 'true') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}
