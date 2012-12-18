<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$userBook = new UserBook();
$userBook->userId = 1;
$total = $userBook->count();
Pager::$pageSize = 5;
$page = Pager::requestPage($total);
$bookIds = array();
foreach ($userBook->find(array('limit' => Pager::limit($page))) as $book) {
    $bookIds[$book->bookId] = $book->bookId;
}
$books = new Books();
$bookList = $books->loads($bookIds);
$html = array('<table border="1">');
foreach ($bookList as $o) {
    $html[] = "<tr><td>{$o->id}</td><td>{$o->name}</td></tr>";
}
$html[] = '</table>';
echo implode("\n", $html);
echo Pager::showPage($total);
?>
