<?php
  require_once '../Qb.php';
  Qb::connect('sqlite:sample.db');
  Qb::db()->exec('CREATE TABLE IF NOT EXISTS todo(id INTEGER PRIMARY KEY, title TEXT, completed INTEGER)');

  if (!empty($_GET['action'])) {
    switch ($_GET['action']) {
    case 'list':
      echo Qb('todo')->toJson();
      exit;
    case 'post':
      Qb('todo')->save(['title' => $_GET['title'], 'completed' => 0]);
      exit;
    case 'delete':
      Qb('todo')->where('id', $_GET['id'])->delete();
      exit;
    case 'change':
      Qb('todo')->where('id', $_GET['id'])->save('completed', $_GET['completed']);
      exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="ja" ng-app="app">
<head>
<meta charset="UTF-8">
<title>ToDo</title>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.1/angular.min.js"></script>
<script>
angular.module('app', []).controller('MainController', function($scope, $http) {
  function api(action, params) {
    params = params || {};
    params.action = action;
    return $http.get('', {params: params});
  }
  function init() {
    $scope.title = '';
    api('list').success(function(res) {
      $scope.todos = res;
    });
  }
  init();
  $scope.post = function() {
    api('post', {title: $scope.title}).success(function() {
      init();
    });
  };
  $scope.delete = function(id) {
    api('delete', {id: id}).success(function() {
      init();
    });
  };
  $scope.change = function(todo) {
    api('change', {id: todo.id, completed: todo.completed});
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
<form ng-submit="post()">
  <input ng-model="title">
  <button>post</button>
</form>
</body>
</html>
