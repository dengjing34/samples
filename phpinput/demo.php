<?php

/**
 * @author jingd <jingd3@jumei.com>
 */
if (isset($_POST['submit'])) {
    $data = file_get_contents('php://input');
    var_dump($data);
}

?>
<form method="post" action="http://api.koubei.jumeicd.com/rpc.php?protocol=json">
    class:<input type="text" name="class"/><br />
    method:<input type="text" name="method"/><br />
    <input type="submit" name="submit" value="submit" />
</form>

<form method="post" action="http://api.koubei.jumeicd.com/rpc.php?protocol=json">
    input:<input type="text" /><br />
    <button type="submit">submit</button>
</form>