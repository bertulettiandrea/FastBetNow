<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Materie - 5IE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .materie-container {
            padding: 40px 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        h1 {
            color: white;
            margin-bottom: 50px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .materia-btn {
            font-size: 1.5rem;
            padding: 40px;
            border-radius: 10px;
            margin: 15px 0;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .materia-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-decoration: none;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
        }
    </style>
</head>
<body>
<a href="index.php" class="btn btn-secondary back-btn">← Indietro</a>

<div class="container materie-container">
    <div class="text-center">
        <h1>Materie</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <a href="gpo.php" class="btn btn-warning materia-btn w-100">GPO</a>
                <a href="tep.php" class="btn btn-info materia-btn w-100">TEP</a>
                <a href="informatica.php" class="btn btn-primary materia-btn w-100">INFORMATICA</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
