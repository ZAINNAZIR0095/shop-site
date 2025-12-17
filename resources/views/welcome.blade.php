<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shop Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>

    <script>
        // Debug script
        console.log('Vue app should load now');

        // Check if Vue is loaded
        setTimeout(() => {
            if (typeof Vue !== 'undefined') {
                console.log('Vue is loaded');
            }
            if (window.axios) {
                console.log('Axios is loaded');
            }
        }, 1000);
    </script>
</body>
</html>
