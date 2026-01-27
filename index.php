<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Homepage - 5IE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .main-container {
            text-align: center;
        }
        .main-btn {
            font-size: 2rem;
            padding: 60px 40px;
            border-radius: 15px;
            margin: 20px;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .main-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-decoration: none;
        }
        h1 {
            color: white;
            margin-bottom: 50px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-container">
        <h1>Benvenuto in 5IE</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <a href="materie.php" class="btn btn-primary main-btn w-100">MATERIE</a>
            </div>
            <div class="col-md-6">
                <a href="FBN/login.php" class="btn btn-success main-btn w-100">FBN</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
