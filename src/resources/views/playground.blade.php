<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex items-center justify-center">


    <div x-data="simpleComponent()">
        <ul>
            <template x-for="num in nums">
                <li x-text="num"></li>
            </template>
        </ul>
    </div>


</div>

<script>
    // Component 1: The Parent Table
    function simpleComponent() {
        return {
            nums: [1,2,3,4]
        }
    }
</script>
 @stack('scripts')
</body>
</html>