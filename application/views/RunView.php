<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>App Runner</title>
    <link href="/static/css/base.css"  media="all" rel="stylesheet" type="text/css" />

</head>
<body>

<div id="container">
    <h1>Logger....</h1>

    <div id="body">

        <form name="f" method=post action='test_controller/write'>
            <input id="q" autofocus>
            <script>
                if (!("autofocus" in document.createElement("input"))) {
                    document.getElementById("q").focus();
                }
            </script>
            <input type="submit" value="Post">
        </form>
    </div>

    <p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>

</body>
</html>