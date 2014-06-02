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

        <?php echo form_open('write', ['name' => 'q', ]); ?>

            <input type='input'  id="u" name="uid" autofocus />
            <input type='input'  id="a" name="aid" />
            <input type='hidden' id="l" name="loc" value="twitter" />

            <script>
                if (!("autofocus" in document.createElement("input"))) {
                    document.getElementById("u").focus();
                }
            </script>
            <?php echo form_submit('', 'log'); ?>
        </form>
    </div>

    <p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>

</body>
</html>