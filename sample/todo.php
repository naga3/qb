<?php
  require_once '../qb.php';
  Qb::connect('sqlite:sample.db');
  Qb::db()->exec('CREATE TABLE IF NOT EXISTS todo(id INTEGER PRIMARY KEY, title TEXT, completed INTEGER)');
  if (isset($_GET['id'])) {
    switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      echo Qb('todo')->toJson();
      break;
    case 'POST':
      $in = json_decode(file_get_contents('php://input'), true);
      if (isset($in['id'])) {
        Qb('todo')->where($in['id'])->save('completed', $in['completed']);
      } else {
        Qb('todo')->save(['title' => $in['title'], 'completed' => 0]);
      }
      break;
    case 'DELETE':
      Qb('todo')->delete($_GET['id']);
      break;
    }
    exit;
  }
?>
<!DOCTYPE html>
<html ng-app="app">
<head>
<meta charset="UTF-8">
<title>ToDo</title>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular-resource.min.js"></script>
<script>
angular.module('app', ['ngResource']).controller('MainController', function($scope, $resource) {
  var res = $resource('?id=:id');
  $scope.todos = res.query();
  $scope.post = function() {
    res.save({title: $scope.title}, function() {
      location.reload();
    });
  };
  $scope.delete = function(id) {
    res.delete({id: id}, function() {
      location.reload();
    });
  };
  $scope.change = function(todo) {
    res.save({id: todo.id, completed: todo.completed});
  };
});
</script>
</head>
<body ng-controller="MainController">
<p ng-repeat="todo in todos">
  <input type="checkbox" ng-model="todo.completed" ng-true-value="'1'" ng-false-value="'0'" ng-click="change(todo)">
  <span ng-style="{textDecoration: todo.completed === '1' ? 'line-through' : 'none'}">{{todo.title}}</span>
  <span style="cursor: pointer" ng-click="delete(todo.id)">✕</span>
</p>
<input ng-model="title">
<button ng-click="post()">post</button>
</body>
</html>
