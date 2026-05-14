<!DOCTYPE html>
<html>
<head>
    <title>Student Life Hub</title>

    <style>

        body{
            font-family: Arial;
            margin: 40px;
            background: #f5f5f5;
        }

        .announcement{
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        .date{
            color: gray;
            font-size: 14px;
        }

    </style>
</head>

<body>

    <h1>📢 Announcements</h1>

    @if(isset($error))
        <p>{{ $error }}</p>
    @endif

    @if(count($announcements) > 0)

        @foreach($announcements as $announcement)

            <div class="announcement">

                <h2>{{ $announcement['title'] }}</h2>

                <p class="date">
                    {{ $announcement['date'] }}
                </p>

                <p>
                    {{ $announcement['content'] }}
                </p>

            </div>

        @endforeach

    @else

        <p>No announcements available.</p>

    @endif

</body>
</html>