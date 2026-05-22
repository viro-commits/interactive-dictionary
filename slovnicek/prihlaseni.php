<?php
session_start();
require 'dbconnect.php';

$error = "";

$citaty = ["Každé nové slovo je nový svět.", "Opakování je matka moudrosti.", "Investice do vědění nese nejlepší úrok.", "Kořeny vzdělání jsou hořké, ale ovoce je sladké.", "Pro život, ne pro školu se učíme.", "Nejlepší část vzdělání je ta, kterou člověk získal sám."];
$nahodny_citat = $citaty[array_rand($citaty)];

// PŘIHLÁŠENÍ - OVĚŘENÍ UŽIVATELE A VYTVOŘENÍ SESSION  
if(isset($_POST["prihlaseni"])){
    $uzivatel = mysqli_real_escape_string($conn, $_POST["username"]);
    $heslo = $_POST["password"];

    // NAČTENÍ UŽIVATELE Z DATABÁZE PODLE JMÉNA
    $sql = "SELECT id, username, password FROM users WHERE username = '$uzivatel'";
    $result = mysqli_query($conn, $sql);

    if($row = mysqli_fetch_assoc($result)){
        // OVĚŘENÍ HESLA (POROVNÁNÍ SE ZAHASHOVANÝM HESLEM V DATABÁZI)
        if(password_verify($heslo, $row["password"])){
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["username"] = $row["username"];

            header("Location: index.php");
            exit();
        }else{
            $error = "Špatné heslo!";
        }
    }else{
        $error = "Uživatel s tímto jménem neexistuje!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Přihlášení</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #e2e0db;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .wrapper {
            width: 100%;
            max-width: 780px;
        }

        /* HEADER */
        .site-header {
            margin-bottom: 24px;
        }

        .site-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #1c1c1c;
            letter-spacing: -0.5px;
        }

        .site-header h1 span {
            color: #cc1100;
        }

        .site-header .tagline {
            margin-top: 4px;
            font-size: 0.88rem;
            color: #888;
        }

        /* HLAVNÍ GRID */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "panel form"
                "footer footer";
            gap: 16px;
            align-items: stretch;
        }

        /* LEVÝ PANEL */
        .panel {
            grid-area: panel;
            background: #eeece8;
            border-radius: 12px;
            padding: 36px 32px;
            border: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .panel .vitej {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1c1c1c;
            margin-bottom: 12px;
        }

        .panel .about-block {
            font-size: 0.95rem;
            color: #444;
            line-height: 1.75;
        }

        .panel .quote-block {
            border-left: 3px solid #cc1100;
            padding-left: 14px;
            margin-top: 28px;
        }

        .panel .quote-block p {
            font-size: 0.86rem;
            color: #888;
            font-style: italic;
            line-height: 1.6;
        }

        .panel .nazev-block {
            margin-top: auto;
            padding-top: 20px;
            font-size: 0.78rem;
            color: #aaa;
        }

        /* FORMULÁŘ */
        .form-box {
            grid-area: form;
            background: #eeece8;
            border-radius: 12px;
            padding: 36px 32px;
            border: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .form-box h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1c1c1c;
            margin-bottom: 4px;
        }

        .form-box label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #666;
        }

        .form-box input[type="text"],
        .form-box input[type="password"] {
            padding: 10px 14px;
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            font-size: 0.9rem;
            outline: none;
        }

        .form-box input:focus {
            border-color: #cc1100;
        }

        .form-box input::placeholder {
            color: #bbb;
        }

        .btn-submit {
            margin-top: 4px;
            padding: 12px;
            background-color: #cc1100;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.15s ease;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #aa0e00;
        }

        .link-back {
            font-size: 0.82rem;
            color: #aaa;
            text-decoration: none;
        }

        .link-back:hover {
            color: #1c1c1c;
        }

        .link-prihlaseni {
            color: #cc1100;
            text-decoration: none;
            font-weight: 500;
        }

        .link-prihlaseni:hover {
            text-decoration: underline;
        }

        /* ERROR HLÁŠKY */
        .error {
            color: #aa0e00;
            font-size: 0.82rem;
            font-weight: 600;
            background-color: #f5ede9;
            border: 1px solid #e8c8c3;
            border-radius: 8px;
            padding: 8px 12px;
        }

        /* PATIČKA */
        .footer {
            grid-area: footer;
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #999;
        }

        /* RESPONSIVE */
        @media (max-width: 560px) {
            .page-grid {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "panel"
                    "form"
                    "footer";
            }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <header class="site-header">
        <h1>Slovníček<span>.</span></h1>
        <p class="tagline">Tvůj osobní interaktivní slovník</p>
    </header>

    <div class="page-grid">

        <!-- LEVÝ PANEL -->
        <div class="panel">
            <div>
                <p class="vitej">Přihlášení</p>
                <p class="about-block">Přihlaš se a pokračuj ve studiu slovíček vlastním tempem.</p>

                <div class="quote-block">
                    <p><?php echo htmlspecialchars($nahodny_citat); ?></p>
                </div>
            </div>

            <div class="nazev-block">Interaktivní Slovníček</div>
        </div>

        <!-- FORMULÁŘ -->
        <div class="form-box">
            <h2>Přihlásit se</h2>

            <?php if($error != ""){ ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>

            <p>Nemáš účet? <a href="registrace.php" class="link-prihlaseni">Zaregistruj se</a></p>

            <form method="POST" action="">

                <label for="username">Uživatelské jméno
                    <input type="text" name="username" placeholder="Uživatelské jméno"
                        value="<?php if(isset($uzivatel)) { echo htmlspecialchars($uzivatel); } ?>" required>
                </label><br>

                <label for="password">Heslo
                    <input type="password" name="password" placeholder="Heslo" required>
                </label><br>

                <input type="submit" name="prihlaseni" value="Přihlásit se" class="btn-submit">

            </form>

            <a href="index.php" class="link-back">Zpět na hlavní stránku</a>
        </div>

    </div>

    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>
</body>
</html>
