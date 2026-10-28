<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso no disponible | TESCHA</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; background: #f6f4f2; color: #263247; font-family: Arial, sans-serif; }
        main { width: min(100%, 560px); padding: 32px; border: 1px solid #ece9e6; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(31, 38, 50, .06); }
        h1 { margin: 0; font-size: 1.4rem; }
        p { color: #596579; font-size: .95rem; line-height: 1.6; }
        .notice { margin-bottom: 0; color: #8791a1; font-size: .82rem; }
    </style>
</head>
<body>
    <main>
        <h1>Acceso al panel no disponible</h1>
        <p>{{ $mensaje }}</p>
        <p class="notice">La cuenta y la información académica se conservaron. Este bloqueo no modifica el estado de la cuenta.</p>
    </main>
</body>
</html>
