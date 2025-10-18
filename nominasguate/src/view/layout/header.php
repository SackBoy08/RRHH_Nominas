<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?? 'Mi Sistema de Nómina' ?></title>

  <!-- Bootstrap CSS (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-…"
        crossorigin="anonymous">

  <!-- Tus estilos -->
  <link rel="stylesheet" href="../public/assets/css/menu.css">
  <link rel="stylesheet" href="/../assets/css/empleados.css">
  <link rel="stylesheet" href="/../assets/css/nominas.css">
  <link rel="stylesheet" href="/../assets/css/liquidaciones.css">
  <link rel="stylesheet" href="/../assets/css/vacasiones.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"/>

</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="index.php?route=menu">Mi Nómina</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item">
        <a class="nav-link" href="index.php?route=logout">Cerrar sesión</a>
      </li>
    </ul>
  </div>
</nav>
<main class="container py-5">
