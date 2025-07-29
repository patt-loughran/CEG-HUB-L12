<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="p-10">

    <div x-data="{ count: 0 }" class="space-y-4">
        <button @click="count++" class="bg-blue-500 text-white px-4 py-2 rounded">
            Increment
        </button>
        <div>
            Count is: <span x-text="count"></span>
        </div>
    </div>

</body>
</html>