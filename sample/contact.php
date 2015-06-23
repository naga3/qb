<?php
  require_once '../Qb.php';
  Qb::connect('sqlite:sample.db');
  Qb::db()->exec('CREATE TABLE IF NOT EXISTS contact(id INTEGER PRIMARY KEY, name TEXT, mail TEXT)');

  if (!empty($_POST['name'])) {
    Qb('contact')->save([
      'name' => $_POST['name'],
      'mail' => $_POST['mail']
    ]);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
  $contacts = Qb('contact')->toObject();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Contact</title>
</head>
<body>
<ul>
  <?php foreach ($contacts as $contact) {
    echo "<li>$contact->name <a href='mailto:$contact->mail'>$contact->mail</a></li>";
  } ?>
</ul>
<form method="post">
  name:<input name="name">
  mail:<input name="mail">
  <input type="submit" value="post">
</form>
</body>
</html>
